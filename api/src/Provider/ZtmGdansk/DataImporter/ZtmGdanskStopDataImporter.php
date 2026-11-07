<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\Provider\ZtmGdansk\DataImporter;

use App\DataImport\ProgressReporterInterface;
use App\DataImport\TableUpserter;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ds\Set;
use Psr\Clock\ClockInterface;

class ZtmGdanskStopDataImporter extends AbstractDataImporter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly TableUpserter $upserter,
        private readonly JsonStreamer $jsonStreamer,
        private readonly IdUtils $idUtils,
        private readonly ZtmGdanskConfig $config,
        private readonly ClockInterface $clock,
    ) {
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $days  = $this->config->scheduleDays;
        $count = $this->connection->transactional(fn () => $this->upserter->upsert(
            table: 'stop',
            rows: $this->getStopsFromZtmApi($reporter),
            providerId: ZtmGdanskProvider::IDENTIFIER,
            import: $event->import,
            types: ['on_demand' => ParameterType::BOOLEAN],
        ));

        $reporter->progress($days, max: $days, comment: sprintf('OK, %d stops', $count), finished: true);
    }

    /**
     * Stops of every day of the window, each once, from the earliest day that lists it.
     *
     * @return \Generator<string, array<string, mixed>> rows by id
     */
    private function getStopsFromZtmApi(ProgressReporterInterface $reporter): \Generator
    {
        $dates = ZtmGdanskProvider::feedDates($this->clock, $this->config->scheduleDays);
        $seen  = new Set();
        $day   = 0;

        foreach ($this->jsonStreamer->streamDated($this->config->stopsUrl, 'stops', $dates) as $date => $stops) {
            $imported = yield from $this->unseenStops($stops, $seen);

            $reporter->progress(++$day, max: count($dates), comment: sprintf('%s: %d stops', $date, $imported));
        }
    }

    /**
     * Passenger stops of a day not seen on an earlier one, which they are marked as.
     *
     * @param iterable<array<string, mixed>> $stops records of the stops feed
     *
     * @return \Generator<string, array<string, mixed>, mixed, int> rows by id, returning how many there were
     */
    private function unseenStops(iterable $stops, Set $seen): \Generator
    {
        $count = 0;

        foreach ($stops as $stop) {
            $id = $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']);

            if (!self::isPassengerStop($stop) || $seen->contains($id)) {
                continue;
            }

            $seen->add($id);
            $count++;

            $name = trim($stop['stopName'] ?? $stop['stopDesc']);

            yield $id => [
                'name'       => $name,
                'variant'    => trim((string) ($stop['stopCode'] ?? $stop['subName'])) ?: null,
                'latitude'   => $stop['stopLat'],
                'longitude'  => $stop['stopLon'],
                'on_demand'  => (bool) $stop['onDemand'],
                'group_name' => $name,
            ];
        }

        return $count;
    }

    private static function isPassengerStop(array $stop): bool
    {
        return !($stop['nonpassenger'] || $stop['virtual'] || $stop['depot']);
    }

    #[\Override]
    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import stops";
    }
}
