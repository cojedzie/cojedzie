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

namespace ClickHouseMigrations;

use App\ClickHouse\Migration;
use ClickHouseDB\Client;

final class Version20261101000000 implements Migration
{
    #[\Override]
    public function getDescription(): string
    {
        return 'Departure history: the last observation of every departure from every stop';
    }

    #[\Override]
    public function up(Client $client): void
    {
        // the retention is the environment's and is applied by ClickHouseDepartureHistory::prepare()
        $client->write(<<<'SQL'
        CREATE TABLE departures (
            provider LowCardinality(String),
            stop_id String,
            line_id String,
            line_symbol LowCardinality(String),
            trip_key String,
            scheduled_at DateTime('UTC'),
            estimated_at Nullable(DateTime('UTC')),
            delay Nullable(Int32) MATERIALIZED dateDiff('second', scheduled_at, estimated_at),
            status Enum8('scheduled' = 1, 'realtime' = 2),
            headsign String,
            vehicle Nullable(String),
            duty LowCardinality(Nullable(String)),
            observed_at DateTime('UTC')
        ) ENGINE = ReplacingMergeTree(observed_at)
        PARTITION BY toYYYYMM(scheduled_at)
        ORDER BY (provider, stop_id, line_id, scheduled_at, trip_key)
        TTL scheduled_at + INTERVAL 365 DAY
        SQL);
    }
}
