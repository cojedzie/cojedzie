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
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ZtmGdanskOperatorsDataImporter extends AbstractDataImporter
{
    final public const RESOURCE_URL = ZtmGdanskProvider::BASE_URL . "/dff5f71f-0134-4ef3-8116-73c1a8e929a5/download/agencies.json";

    public function __construct(
        private readonly Connection $connection,
        private readonly HttpClientInterface $httpClient,
        private readonly IdUtils $idUtils
    ) {
    }

    public function import(ProgressReporterInterface $reporter)
    {
        $this->connection->beginTransaction();

        $existing = $this->connection->createQueryBuilder()
            ->from('operator', 'o')
            ->select('id', 'name', 'email', 'url', 'phone')
            ->where('o.provider_id = :provider_id')
            ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
            ->execute()
            ->fetchAllAssociativeIndexed();

        foreach ($this->getOperatorsFromZtmApi() as $id => $operator) {
            if (array_key_exists($id, $existing)) {
                $this->connection->update(
                    'operator',
                    $operator,
                    [
                        'id'          => $id,
                        'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                    ]
                );
            } else {
                $this->connection->insert(
                    'operator',
                    array_merge(
                        [
                            'id'          => $id,
                            'provider_id' => ZtmGdanskProvider::IDENTIFIER,
                        ],
                        $operator
                    )
                );
            }
        }

        $this->connection->commit();
    }

    private function getOperatorsFromZtmApi()
    {
        $response = $this->httpClient->request('GET', self::RESOURCE_URL);

        foreach ($response->toArray()['agency'] as $operator) {
            yield $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $operator['agencyId']) => [
                'name'  => $operator['agencyName'],
                'email' => $operator['agencyEmail'] ?? null,
                'url'   => $operator['agencyUrl'] ?? null,
                'phone' => $operator['agencyPhone'] ?? null,
            ];
        }
    }

    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import operators";
    }
}
