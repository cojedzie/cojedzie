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
            table: 'line',
            rows: $this->getLinesFromZtmApi($reporter),
            providerId: ZtmGdanskProvider::IDENTIFIER,
            import: $event->import,
        ));

        $days = $this->config->scheduleDays;
        $reporter->progress($days, max: $days, comment: sprintf('OK, %d lines', $count), finished: true);
    }

    #[\Override]
    public function getDependencies(): array
    {
        return [
            ZtmGdanskOperatorsDataImporter::class,
        ];
    }

    /**
     * Lines of known operators of every day of the window, each once, from the earliest day that lists it.
     *
     * @return \Generator<string, array<string, mixed>> rows by id
     */
    private function getLinesFromZtmApi(ProgressReporterInterface $reporter): \Generator
    {
        $dates     = ZtmGdanskProvider::feedDates($this->clock, $this->config->scheduleDays);
        $operators = $this->knownOperators();
        $seen      = new Set();
        $day       = 0;

        foreach ($this->jsonStreamer->streamDated($this->config->routesUrl, 'routes', $dates) as $date => $lines) {
            $imported = yield from $this->unseenLines($lines, $operators, $seen);

            $reporter->progress(++$day, max: count($dates), comment: sprintf('%s: %d lines', $date, $imported));
        }
    }

    private function knownOperators(): Set
    {
        return new Set(
            $this->connection->createQueryBuilder()
                ->from('operator', 'o')
                ->select('id')
                ->where('o.provider_id = :provider_id')
                ->setParameter('provider_id', ZtmGdanskProvider::IDENTIFIER)
                ->executeQuery()
                ->iterateColumn()
        );
    }

    /**
     * Lines of a day not seen on an earlier one, which they are marked as; lines of unknown operators are skipped.
     *
     * @param iterable<array<string, mixed>> $lines records of the routes feed
     *
     * @return \Generator<string, array<string, mixed>, mixed, int> rows by id, returning how many there were
     */
    private function unseenLines(iterable $lines, Set $operators, Set $seen): \Generator
    {
        $count = 0;

        foreach ($lines as $line) {
            $id       = $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $line['routeId']);
            $symbol   = (string) $line['routeShortName'];
            $operator = $this->idUtils->generate(ZtmGdanskProvider::IDENTIFIER, $line['agencyId']);

            if ($seen->contains($id) || !$operators->contains($operator)) {
                continue;
            }

            $seen->add($id);
            $count++;

            yield $id => [
                'symbol'      => $line['routeShortName'],
                'type'        => $this->getLineType($line),
                'night'       => preg_match('/^N\d{1,3}$/', $symbol),
                // a single letter, except N
                'fast'        => preg_match('/^[A-MO-Z]$/', $symbol),
                'operator_id' => $operator,
            ];
        }

        return $count;
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
