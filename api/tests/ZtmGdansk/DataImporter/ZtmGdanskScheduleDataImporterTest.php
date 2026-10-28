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

namespace App\Tests\ZtmGdansk\DataImporter;

use App\DataImport\MilestoneType;
use App\DataImport\ProgressReporterInterface;
use App\Entity\ImportEntity;
use App\Event\DataUpdateEvent;
use App\Parser\Exception\StreamOpenException;
use App\Provider\ZtmGdansk\DataImporter\ZtmGdanskScheduleDataImporter;
use App\Provider\ZtmGdansk\ZtmGdanskConfig;
use App\Provider\ZtmGdansk\ZtmGdanskProvider;
use App\Service\IdUtils;
use App\Service\JsonStreamer;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\Uid\Uuid;

/**
 * The schedule is imported line by line, for the lines of this provider updated by the current import.
 */
final class ZtmGdanskScheduleDataImporterTest extends KernelTestCase
{
    private const string PROVIDER = ZtmGdanskProvider::IDENTIFIER;
    private Connection $connection;
    private ImportEntity $import;

    /**
     * @var list<string>
     */
    private array $warnings = [];

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->connection = self::getContainer()->get(Connection::class);
        $this->connection->beginTransaction();

        $this->import = new ImportEntity();
        new \ReflectionProperty($this->import, 'id')->setValue($this->import, Uuid::v4());

        $this->connection->insert('import', ['id' => $this->import->getId(),
            'started_at'                          => new \DateTime()], ['id' => 'uuid',
                'started_at'                                                 => 'datetime']);
        $this->connection->insert('provider', ['id' => self::PROVIDER,
            'name'                                  => 'ZTM',
            'class'                                 => ZtmGdanskProvider::class,
            'update_date'                           => '2026-10-28 00:00:00']);
        $this->connection->insert('provider', ['id' => 'other',
            'name'                                  => 'Other',
            'class'                                 => 'Other',
            'update_date'                           => '2026-10-28 00:00:00']);
        $this->connection->insert('operator', ['id' => self::PROVIDER . '::1',
            'provider_id'                           => self::PROVIDER,
            'name'                                  => 'ZTM']);
        $this->connection->insert('stop', ['id' => self::PROVIDER . '::100',
            'provider_id'                       => self::PROVIDER,
            'name'                              => 'Brama Wyżynna',
            'on_demand'                         => 0]);
    }

    #[\Override]
    protected function tearDown(): void
    {
        $this->connection->rollBack();

        parent::tearDown();
    }

    public function testTripStopsAreStampedWithTheImport(): void
    {
        $this->line(self::PROVIDER . '::6', $this->import->getId());

        $this->importer(fn (string $route) => [$this->row($route)])->import($this->reporter(), new DataUpdateEvent($this->import));

        $this->assertSame(
            [$this->import->getId()->toBinary()],
            $this->connection->fetchFirstColumn('SELECT import_id FROM trip_stop'),
        );
    }

    public function testOnlyLinesOfThisProviderAreImported(): void
    {
        $this->line(self::PROVIDER . '::6', $this->import->getId());
        $this->line('other::7', $this->import->getId());

        $routes = [];
        $this->importer(function (string $route) use (&$routes) {
            $routes[] = $route;

            return [];
        })->import($this->reporter(), new DataUpdateEvent($this->import));

        $this->assertSame(['6'], $routes);
    }

    public function testLineWithUnavailableScheduleIsSkipped(): void
    {
        $this->line(self::PROVIDER . '::6', $this->import->getId());
        $this->line(self::PROVIDER . '::8', $this->import->getId());

        $this->importer(fn (string $route) => $route === '6'
            ? throw new StreamOpenException('Cannot open')
            : [$this->row($route)])->import($this->reporter(), new DataUpdateEvent($this->import));

        $this->assertSame([self::PROVIDER . '::8-8-1-1'], $this->connection->fetchFirstColumn('SELECT id FROM trip'));
        $this->assertCount(1, $this->warnings);
    }

    private function line(string $id, Uuid $import): void
    {
        $this->connection->insert('line', [
            'id'          => $id,
            'provider_id' => explode('::', $id)[0],
            'import_id'   => $import,
            'symbol'      => explode('::', $id)[1],
            'type'        => 'bus',
            'fast'        => 0,
            'night'       => 0,
        ], ['import_id' => 'uuid']);

        $this->connection->insert('track', ['id' => sprintf('%s::R%sT1', explode('::', $id)[0], explode('::', $id)[1]),
            'line_id'                            => $id]);
    }

    private function row(string $route): array
    {
        return [
            'routeId'         => $route,
            'busServiceName'  => $route,
            'tripId'          => 1,
            'order'           => 1,
            'agencyId'        => 1,
            'noteDescription' => null,
            'date'            => '2026-10-28',
            'arrivalTime'     => '1899-12-30T12:00:00',
            'departureTime'   => '1899-12-30T12:01:00',
            'stopId'          => 100,
            'stopSequence'    => 0,
        ];
    }

    /**
     * @param \Closure(string $schedule): iterable<array<string, mixed>> $schedule
     */
    private function importer(\Closure $schedule): ZtmGdanskScheduleDataImporter
    {
        $streamer = new class($schedule) extends JsonStreamer {
            public function __construct(
                private readonly \Closure $schedule
            ) {
            }

            #[\Override]
            public function stream(string $url, string $branch): iterable
            {
                parse_str((string) parse_url($url, PHP_URL_QUERY), $query);

                return ($this->schedule)($query['routeId']);
            }
        };

        return new ZtmGdanskScheduleDataImporter(
            $this->connection,
            new IdUtils(),
            $streamer,
            new ZtmGdanskConfig('https://ztm.example', '', '', '', '', '', ''),
            new MockClock('2026-10-28 12:00:00'),
        );
    }

    private function reporter(): ProgressReporterInterface
    {
        $reporter = $this->createStub(ProgressReporterInterface::class);
        $reporter->method('milestone')->willReturnCallback(function (string $comment, MilestoneType $type = MilestoneType::Info) {
            if ($type === MilestoneType::Warning) {
                $this->warnings[] = $comment;
            }
        });

        return $reporter;
    }
}
