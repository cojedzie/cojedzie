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
use App\Model\Line as LineModel;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\IterableUtils;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZtmGdanskLineDataImporter extends AbstractDataImporter
{
    final public const RESOURCE_URL = ZtmGdanskProvider::BASE_URL . "/22313c56-5acf-41c7-a5fd-dc5dc72b3851/download/routes.json";

    final public const ZTM_TYPE_MAPPING = [
        2 => LineModel::TYPE_TRAM,
        5 => LineModel::TYPE_TROLLEYBUS,
    ];

    public function __construct(
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient,
        private readonly IdUtils $idUtils
    ) {
    }

    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event)
    {
        $this->connection->beginTransaction();

        $query = $this->connection->createQueryBuilder()
            ->from('line', 'l')
            ->select('id')
            ->where('l.provider_id = :provider_id')
            ->andWhere('l.id IN (:ids)')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER);

        $count = 0;
        foreach (IterableUtils::batch($this->getLinesFromZtmApi(), 100) as $batch) {
            $ids = array_keys($batch);
            $query->setParameter('ids', $ids, Connection::PARAM_STR_ARRAY);
            $existing = $query->execute()->fetchFirstColumn();

            foreach ($batch as $id => $line) {
                if (in_array($id, $existing, true)) {
                    $this->connection->update(
                        'line',
                        [
                            ...$line,
                            'import_id' => $event->import->getId(),
                        ],
                        [
                            'id'          => $id,
                            'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                        ],
                        [
                            'import_id' => 'uuid',
                        ]
                    );
                } else {
                    $this->connection->insert(
                        'line',
                        array_merge(
                            [
                                'id'          => $id,
                                'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                                'import_id'   => $event->import->getId(),
                            ],
                            $line
                        ),
                        [
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

    public function getDependencies(): array
    {
        return [
            ZtmGdanskOperatorsDataImporter::class,
        ];
    }

    private function getLinesFromZtmApi()
    {
        $response = $this->httpClient->request('GET', self::RESOURCE_URL);
        $lines    = $response->toArray()[date('Y-m-d')]['routes'];

        $operators = $this->connection->createQueryBuilder()
            ->from('operator', 'o')
            ->select('id')
            ->where('o.provider_id = :provider_id')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
            ->execute()
            ->fetchFirstColumn()
            ;

        foreach ($lines as $line) {
            $symbol   = $line['routeShortName'];
            $operator = $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $line['agencyId']);

            // skip unknown operators
            if (!in_array($operator, $operators, true)) {
                continue;
            }

            yield $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $line['routeId']) => [
                'symbol'      => $symbol,
                'type'        => $this->getLineType($line),
                'night'       => preg_match('/^N\d{1,3}$/', (string) $symbol),
                'fast'        => preg_match('/^[A-MO-Z]$/', (string) $symbol),
                'operator_id' => $operator,
            ];
        }
    }

    private function getLineType(array $line)
    {
        return self::ZTM_TYPE_MAPPING[$line['agencyId']] ?? LineModel::TYPE_BUS;
    }

    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import lines";
    }
}
