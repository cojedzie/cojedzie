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

namespace App\ClickHouse;

use App\DepartureHistory\DepartureHistory;
use App\DepartureHistory\DepartureHistoryException;
use App\Provider\Live\LiveDeparture;
use Kadet\Functional\Collections as c;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The departure history in the ClickHouse table departures, a ReplacingMergeTree versioned by observed_at: every
 * snapshot is inserted whole and merges keep the newest row of each departure.
 */
final readonly class ClickHouseDepartureHistory implements DepartureHistory
{
    private const string TABLE = 'departures';

    public function __construct(
        private ClickHouseConnection $connection,
        private Migrator $migrator,
        #[Autowire(env: 'int:COJEDZIE_DEPARTURE_HISTORY_RETENTION_DAYS')]
        private int $retentionDays,
    ) {
    }

    #[\Override]
    public function isEnabled(): bool
    {
        return $this->connection->isEnabled();
    }

    #[\Override]
    public function prepare(): void
    {
        try {
            $this->migrator->migrate();
            $this->applyRetention();
        } catch (\Exception $exception) {
            throw new DepartureHistoryException('Departure history could not be prepared: ' . $exception->getMessage(), previous: $exception);
        }
    }

    #[\Override]
    public function record(string $provider, \DateTimeImmutable $observedAt, iterable $departures): void
    {
        $rows = $departures
            |> c\toSeq()
            |> c\map(fn (LiveDeparture $departure) => [
                'provider'     => $provider,
                'stop_id'      => $departure->stopId,
                'line_id'      => $departure->lineId,
                'line_symbol'  => $departure->lineSymbol,
                'trip_key'     => $departure->tripKey,
                'scheduled_at' => self::time($departure->scheduledAt),
                'estimated_at' => $departure->estimatedAt !== null ? self::time($departure->estimatedAt) : null,
                'status'       => $departure->status->value,
                'headsign'     => $departure->headsign,
                'vehicle'      => $departure->vehicle,
                'duty'         => $departure->duty,
                'observed_at'  => self::time($observedAt),
            ])
            |> c\toArray();

        if ($rows === []) {
            return;
        }

        try {
            $this->connection->client()->insertAssocBulk(self::TABLE, $rows);
        } catch (\Exception $exception) {
            throw new DepartureHistoryException('Departures could not be recorded: ' . $exception->getMessage(), previous: $exception);
        }
    }

    /**
     * Changing the TTL rewrites the table's parts, so it is changed only when the configured retention differs.
     */
    private function applyRetention(): void
    {
        $client = $this->connection->client();
        $engine = $client
            ->select('SELECT engine_full FROM system.tables WHERE database = currentDatabase() AND name = :table', ['table' => self::TABLE])
            ->fetchOne('engine_full');

        if (str_contains((string) $engine, sprintf('TTL scheduled_at + toIntervalDay(%d)', $this->retentionDays))) {
            return;
        }

        $client->write(sprintf('ALTER TABLE %s MODIFY TTL scheduled_at + INTERVAL %d DAY', self::TABLE, $this->retentionDays));
    }

    private static function time(\DateTimeImmutable $time): string
    {
        return $time->setTimezone(new \DateTimeZone('UTC'))->format('Y-m-d H:i:s');
    }
}
