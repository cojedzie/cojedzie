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
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use App\Utility\IterableUtils;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\ParameterType;
use Ds\Set;

class ZtmGdanskStopDataImporter extends AbstractDataImporter
{
    public function __construct(
        private readonly Connection $connection,
        private readonly JsonStreamer $jsonStreamer,
        private readonly IdUtils $idUtils,
        private readonly ZtmGdanskConfig $config,
    ) {
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->connection->beginTransaction();

        $query = $this->connection->createQueryBuilder()
            ->from('stop', 's')
            ->select('id')
            ->where('s.provider_id = :provider_id')
            ->andWhere('s.id IN (:ids)')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER);

        $count = 0;
        foreach (IterableUtils::batch($this->getStopsFromZtmApi(), 100) as $batch) {
            $query->setParameter('ids', array_keys($batch), Connection::PARAM_STR_ARRAY);

            $existing = new Set($query->execute()->iterateColumn());

            foreach ($batch as $id => $stop) {
                if ($existing->contains($id)) {
                    $this->connection->update(
                        'stop',
                        [
                            ...$stop,
                            'import_id' => $event->import->getId(),
                        ],
                        [
                            'id'          => $id,
                            'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                        ],
                        [
                            'on_demand' => ParameterType::BOOLEAN,
                            'import_id' => 'uuid',
                        ]
                    );
                } else {
                    $this->connection->insert(
                        'stop',
                        array_merge(
                            [
                                'id'          => $id,
                                'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                                'import_id'   => $event->import->getId(),
                            ],
                            $stop
                        ),
                        [
                            'on_demand' => ParameterType::BOOLEAN,
                            'import_id' => 'uuid',
                        ]
                    );
                }
            }

            $reporter->progress($count += is_countable($batch) ? count($batch) : 0);
        }

        $reporter->progress($count, comment: 'OK', finished: true);
        $this->connection->commit();
    }

    private function getStopsFromZtmApi()
    {
        foreach ($this->jsonStreamer->stream($this->config->stopsUrl, sprintf('%s.stops', date('Y-m-d'))) as $stop) {
            // skip stops that are technical
            if ($stop['nonpassenger'] || $stop['virtual'] || $stop['depot']) {
                continue;
            }

            $name = trim($stop['stopName'] ?? $stop['stopDesc']);
            yield $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $stop['stopId']) => [
                'name'       => $name,
                'variant'    => trim((string) ($stop['stopCode'] ?? $stop['subName'])) ?: null,
                'latitude'   => $stop['stopLat'],
                'longitude'  => $stop['stopLon'],
                'on_demand'  => (bool) $stop['onDemand'],
                'group_name' => $name,
            ];
        }
    }

    #[\Override]
    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import stops";
    }
}
