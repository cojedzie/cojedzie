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

namespace App\DepartureHistory;

use App\Provider\Live\LiveDepartureSource;
use Psr\Clock\ClockInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * Records what every provider's live feed says right now into the departure history. A failing provider or storage
 * is logged and never stops the others: the next snapshot comes soon anyway.
 */
final class DepartureRecorder implements LoggerAwareInterface
{
    use LoggerAwareTrait;
    private bool $prepared = false;

    /**
     * @param iterable<LiveDepartureSource> $sources
     */
    public function __construct(
        #[AutowireIterator('app.live_departure_source')]
        private readonly iterable $sources,
        private readonly DepartureHistory $history,
        private readonly ClockInterface $clock,
    ) {
        $this->logger = new NullLogger();
    }

    public function isEnabled(): bool
    {
        return $this->history->isEnabled();
    }

    /**
     * @return bool whether every provider was recorded
     */
    public function record(): bool
    {
        if (!$this->prepare()) {
            return false;
        }

        $succeeded = true;

        foreach ($this->sources as $source) {
            try {
                $departures = $source->all();
                $this->history->record($source->getProviderIdentifier(), $this->now(), $departures);
            } catch (\Throwable $exception) {
                $succeeded = false;
                $this->logger->warning('Departures of {provider} were not recorded: {message}', [
                    'provider'  => $source->getProviderIdentifier(),
                    'message'   => $exception->getMessage(),
                    'exception' => $exception,
                ]);
            }
        }

        return $succeeded;
    }

    /**
     * Once per process; after a failure, again on the next snapshot.
     */
    private function prepare(): bool
    {
        if ($this->prepared) {
            return true;
        }

        try {
            $this->history->prepare();

            return $this->prepared = true;
        } catch (\Throwable $exception) {
            $this->logger->warning('Departure history could not be prepared: {message}', [
                'message'   => $exception->getMessage(),
                'exception' => $exception,
            ]);

            return false;
        }
    }

    private function now(): \DateTimeImmutable
    {
        return \DateTimeImmutable::createFromInterface($this->clock->now());
    }
}
