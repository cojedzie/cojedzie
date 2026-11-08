<?php
/*
 * Copyright (C) 2026 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace App\Provider\Database;

use App\DepartureHistory\DepartureHistory;
use App\DepartureHistory\DepartureHistoryException;
use App\DepartureHistory\ObservedDeparture;
use App\Dto\Departure;
use App\Dto\DepartureState;
use App\Dto\TimetableRange;
use App\Dto\Vehicle;
use App\Filter\Requirement\Requirement;
use App\Provider\DeparturePairing;
use App\Provider\TimetableRepository;
use Carbon\Carbon;
use Ds\Seq;
use Kadet\Functional\Collections as c;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Exclude;
use function App\Functions\setup;

/**
 * A timetable whose departures tell what is known of them from the departure history (ADR 0030): planned entries are
 * paired with recorded departures by stop, line and scheduled minute. A departure whose estimated (else scheduled)
 * time has passed is departed, with the last recorded estimate and vehicle; one that hasn't is realtime only while
 * its estimate is younger than the maximum age, and planned otherwise. Recorded departures without an entry are not
 * added. Without the history, or when reading it fails, entries are planned or, once their scheduled time has passed,
 * departed.
 */
#[Exclude] // built by each provider around its own timetable
final readonly class ObservedTimetableRepository implements TimetableRepository
{
    public function __construct(
        private TimetableRepository $inner,
        private DepartureHistory $history,
        private ClockInterface $clock,
        private LoggerInterface $logger,
        private string $provider,
        // how old an estimate may be to be shown, any DateInterval::createFromDateString() string
        private string $maxAge,
    ) {
    }

    #[\Override]
    public function first(Requirement ...$requirements)
    {
        return $this->inner->first(...$requirements);
    }

    #[\Override]
    public function all(Requirement ...$requirements): Seq
    {
        return $this->inner->all(...$requirements);
    }

    #[\Override]
    public function range(): ?TimetableRange
    {
        return $this->inner->range();
    }

    #[\Override]
    public function departures(Requirement ...$requirements): Seq
    {
        $planned = $this->inner->departures(...$requirements);
        $now     = $this->clock->now();
        // estimates observed since then are fresh
        $fresh = $now->sub(\DateInterval::createFromDateString($this->maxAge));

        $pairs = DeparturePairing::pair(
            $planned,
            $this->observed($planned),
            fn (Departure $departure) => DeparturePairing::key($departure->getStop()->getId(), $departure->getLine()->getId(), $departure->getScheduled()),
            fn (ObservedDeparture $observed) => DeparturePairing::key($observed->departure->stopId, $observed->departure->lineId, $observed->departure->scheduledAt),
        );

        return $pairs
            |> c\filter(fn (array $pair) => $pair['planned'] !== null)
            |> c\map(fn (array $pair) => $this->enrich($pair['planned'], $pair['live'], $now, $fresh));
    }

    private function enrich(Departure $planned, ?ObservedDeparture $observed, \DateTimeImmutable $now, \DateTimeImmutable $fresh): Departure
    {
        return setup(clone $planned, function (Departure $departure) use ($observed, $now, $fresh) {
            $live      = $observed?->departure;
            $estimated = $live?->estimatedAt !== null ? Carbon::instance($live->estimatedAt)->tz('UTC') : null;

            if (($estimated ?? $departure->getScheduled()) < $now) {
                // the last recorded state is the outcome, whatever its age
                $departure->setEstimated($estimated);
                $departure->setVehicle($live?->vehicle !== null ? Vehicle::reference($live->vehicle) : null);
                $departure->setState(DepartureState::Departed);
            } elseif ($estimated !== null && $observed->observedAt >= $fresh) {
                $departure->setEstimated($estimated);
                $departure->setVehicle($live->vehicle !== null ? Vehicle::reference($live->vehicle) : null);
                $departure->setState(DepartureState::Realtime);
            } else {
                $departure->setEstimated(null);
                $departure->setState(DepartureState::Planned);
            }
        });
    }

    /**
     * Recorded departures of the stops and lines of the planned ones, scheduled in the minutes they span.
     *
     * @param Seq<Departure> $planned
     *
     * @return Seq<ObservedDeparture>
     */
    private function observed(Seq $planned): Seq
    {
        if ($planned->isEmpty() || !$this->history->isEnabled()) {
            return new Seq();
        }

        $scheduled = $planned |> c\map(fn (Departure $departure) => $departure->getScheduled()->toDateTimeImmutable());

        try {
            return $this->history->observed(
                $this->provider,
                $scheduled |> c\min(),
                ($scheduled |> c\max())->modify('+1 minute'),
                $planned |> c\map(fn (Departure $departure) => $departure->getStop()->getId()) |> c\unique() |> c\toArray(),
                $planned |> c\map(fn (Departure $departure) => $departure->getLine()->getId()) |> c\unique() |> c\toArray(),
            );
        } catch (DepartureHistoryException $exception) {
            $this->logger->warning('Departure history could not be read for the timetable of {provider}: {message}', [
                'provider'  => $this->provider,
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return new Seq();
        }
    }
}
