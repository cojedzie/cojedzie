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

namespace App\Tests\Service;

use App\DataImport\DataImporter;
use App\DataImport\ProgressReporterFactory;
use App\DataImport\ProgressReporterInterface;
use App\Event\DataUpdateEvent;
use App\Service\DataUpdater;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;

final class DataUpdaterTest extends KernelTestCase
{
    public function testImportersRunAfterTheirDependenciesAndTheImportIsFinished(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->beginTransaction();

        try {
            /** @var \ArrayObject<int, array{string, DataUpdateEvent}> $log */
            $log   = new \ArrayObject();
            $lines = $this->importer('lines', [], $log);
            $stops = $this->importer('stops', [$lines::class], $log);

            $this->updater($em, [$stops, $lines])->update();

            $this->assertSame(['lines', 'stops'], array_column($log->getArrayCopy(), 0));
            $this->assertSame('2026-10-28 12:00:00', $log[0][1]->import->getStartedAt()->format('Y-m-d H:i:s'));
            $this->assertSame('2026-10-28 12:00:00', $log[0][1]->import->getFinishedAt()->format('Y-m-d H:i:s'));
        } finally {
            $em->getConnection()->rollBack();
        }
    }

    public function testMemoryLimitIsRestored(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->beginTransaction();

        $limit = ini_get('memory_limit');

        try {
            $this->updater($em, [])->update();

            $this->assertSame($limit, ini_get('memory_limit'));
        } finally {
            ini_set('memory_limit', $limit);
            $em->getConnection()->rollBack();
        }
    }

    public function testMemoryInUseAboveThePreviousLimitIsNotAnError(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->beginTransaction();

        $limit = ini_get('memory_limit');
        ini_set('memory_limit', (string) (memory_get_usage(true) + (32 << 20)));

        // keeps 64 MB in use after the import, more than the limit before it allowed
        $hoarder = new class() extends RecordingImporter {
            public string $data = '';

            public function __construct()
            {
                parent::__construct('hoarder', [], new \ArrayObject());
            }

            #[\Override]
            public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
            {
                $this->data = str_repeat('x', 64 << 20);
            }
        };

        $errors = [];
        set_error_handler(function (int $level, string $message) use (&$errors) {
            $errors[] = $message;

            return true;
        });

        try {
            $this->updater($em, [$hoarder])->update();

            $this->assertSame([], $errors);
        } finally {
            restore_error_handler();
            $hoarder->data = '';
            ini_set('memory_limit', $limit);
            $em->getConnection()->rollBack();
        }
    }

    public function testImportersDependingOnSeveralReadyOnesRun(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->beginTransaction();

        try {
            /** @var \ArrayObject<int, array{string, DataUpdateEvent}> $log */
            $log      = new \ArrayObject();
            $provider = $this->importer('provider', [], $log);
            $stops    = $this->importer('stops', [$provider::class], $log);
            $lines    = $this->importer('lines', [$provider::class], $log);
            $tracks   = $this->importer('tracks', [$stops::class, $lines::class], $log);

            $this->updater($em, [$tracks, $lines, $stops, $provider])->update();

            $order = array_column($log->getArrayCopy(), 0);
            $this->assertSame('provider', $order[0]);
            $this->assertSame('tracks', $order[3]);
            $this->assertEqualsCanonicalizing(['stops', 'lines'], [$order[1], $order[2]]);
        } finally {
            $em->getConnection()->rollBack();
        }
    }

    public function testUnresolvableDependenciesFail(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->beginTransaction();

        try {
            /** @var \ArrayObject<int, array{string, DataUpdateEvent}> $log */
            $log   = new \ArrayObject();
            $lines = $this->importer('lines', [], $log);
            $stops = $this->importer('stops', ['App\\NoSuchImporter'], $log);

            try {
                $this->updater($em, [$lines, $stops])->update();
                $this->fail('The update should have failed.');
            } catch (\LogicException $exception) {
                $this->assertStringContainsString($stops::class, $exception->getMessage());
            }

            // the order is resolved before any importer runs
            $this->assertSame([], $log->getArrayCopy());
        } finally {
            $em->getConnection()->rollBack();
        }
    }

    private function updater(EntityManagerInterface $em, array $importers): DataUpdater
    {
        return new DataUpdater(
            $em,
            new ProgressReporterFactory(new NullLogger()),
            $importers,
            new MockClock('2026-10-28 12:00:00'),
        );
    }

    private function importer(string $name, array $dependencies, \ArrayObject $log): DataImporter
    {
        // each anonymous class declaration is a distinct class, dependencies are given by class name
        return match ($name) {
            'provider' => new class($name, $dependencies, $log) extends RecordingImporter {},
            'lines'    => new class($name, $dependencies, $log) extends RecordingImporter {},
            'stops'    => new class($name, $dependencies, $log) extends RecordingImporter {},
            'tracks'   => new class($name, $dependencies, $log) extends RecordingImporter {},
            default    => throw new \LogicException(sprintf('No importer named %s.', $name)),
        };
    }
}

abstract class RecordingImporter implements DataImporter
{
    public function __construct(
        private readonly string $name,
        private readonly array $dependencies,
        private readonly \ArrayObject $log,
    ) {
    }

    #[\Override]
    public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
    {
        $this->log[] = [$this->name, $event];
    }

    #[\Override]
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    #[\Override]
    public function getDescription(): string
    {
        return $this->name;
    }
}
