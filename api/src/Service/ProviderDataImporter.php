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

use App\DataImport\DataImporter;
use App\DataImport\MilestoneType;
use App\DataImport\ProgressReporterInterface;
use App\Provider\Provider;
use Doctrine\DBAL\Connection;

class ProviderDataImporter implements DataImporter
{
    /**
     * @param \App\Provider\Provider[] $providers
     */
    public function __construct(private readonly Connection $connection, private readonly iterable $providers)
    {
    }

    public function import(ProgressReporterInterface $reporter)
    {
        $existing = $this->connection->createQueryBuilder()
            ->from('provider', 'p')
            ->select('id')
            ->execute()
            ->fetchFirstColumn();

        foreach ($this->providers as $provider) {
            $data = [
                'name'        => $provider->getName(),
                'class'       => $provider::class,
                'update_date' => date('Y-m-d H:i:s'),
            ];

            $id = $provider->getIdentifier();

            if (in_array($id, $existing, true)) {
                $this->connection->update('provider', $data, ['id' => $id]);
            } else {
                $this->connection->insert('provider', array_merge($data, ['id' => $id]));
            }

            $reporter->milestone($provider->getName(), type: MilestoneType::Success);
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

    public function getDescription(): string
    {
        return 'Synchronise provider entities';
    }
}
