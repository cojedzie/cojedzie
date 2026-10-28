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
use App\Dto\Line as LineModel;
use App\Event\DataUpdateEvent;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\AbstractDataImporter;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use Doctrine\DBAL\Connection;
use Ds\Set;
use Psr\Clock\ClockInterface;

class ZtmGdanskLineDataImporter extends AbstractDataImporter
{
    final public const array ZTM_TYPE_MAPPING = [
        2 => LineModel::TYPE_TRAM,
        5 => LineModel::TYPE_TROLLEYBUS,
    ];

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
        $count = $this->connection->transactional(fn () => $this->upserter->upsert(
            'line',
            $this->getLinesFromZtmApi(),
            ZtmGdanskProvider::IDENTIFIER,
            $event->import,
            reporter: $reporter,
        ));

        $reporter->progress($count, comment: 'OK', finished: true);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            ZtmGdanskOperatorsDataImporter::class,
        ];
    }

    private function getLinesFromZtmApi()
    {
        $operators = new Set(
            $this->connection->createQueryBuilder()
                ->from('operator', 'o')
                ->select('id')
                ->where('o.provider_id = :provider_id')
                ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
                ->executeQuery()
                ->iterateColumn()
        );

        foreach ($this->jsonStreamer->stream($this->config->routesUrl, sprintf('%s.routes', ZtmGdanskProvider::feedDate($this->clock))) as $line) {
            $symbol   = $line['routeShortName'];
            $operator = $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $line['agencyId']);

            // skip unknown operators
            if (!$operators->contains($operator)) {
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

    #[\Override]
    public function getDescription(): string
    {
        return "[ZTM Gdańsk] Import lines";
    }
}
