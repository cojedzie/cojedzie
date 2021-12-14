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

namespace App\Service;

use App\Provider\Provider;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use Doctrine\DBAL\Connection;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class ProviderDataImporter implements DataImporter
{
    private Connection $connection;
    /** @var iterable<Provider> */
    private iterable $providers;

    public function __construct(Connection $connection, iterable $providers)
    {
        $this->connection = $connection;
        $this->providers = $providers;
    }

    public function import()
    {
        $existing = $this->connection->createQueryBuilder()
            ->from('provider', 'p')
            ->select('id')
            ->execute()
            ->fetchFirstColumn();

        foreach ($this->providers as $provider) {
            $data = [
                'name'        => $provider->getName(),
                'class'       => get_class($provider),
                'update_date' => date('Y-m-d H:i:s'),
            ];

            $id = $provider->getIdentifier();

            if (in_array($id, $existing, true)) {
                $this->connection->update('provider', $data, ['id' => $id]);
            } else {
                $this->connection->insert('provider', array_merge($data, ['id' => $id]));
            }
        }
    }

    public function isOutdated(): bool
    {
        return true;
    }

    public function getDependencies(): array
    {
        return [];
    }

    public function getPriority(): int
    {
        return 1024;
    }
}
