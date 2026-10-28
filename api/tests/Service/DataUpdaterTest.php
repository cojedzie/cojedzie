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

final class DataUpdaterTest extends KernelTestCase
{
    public function testImportersRunAfterTheirDependenciesAndTheImportIsFinished(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->beginTransaction();

        try {
            $log   = new \ArrayObject();
            $lines = $this->importer('lines', [], $log);
            $stops = $this->importer('stops', [$lines::class], $log);

            new DataUpdater($em, new ProgressReporterFactory(new NullLogger()), [$stops, $lines])->update();

            $this->assertSame(['lines', 'stops'], array_column($log->getArrayCopy(), 0));
            $this->assertNotNull($log[0][1]->import->getFinishedAt());
        } finally {
            $em->getConnection()->rollBack();
        }
    }

    private function importer(string $name, array $dependencies, \ArrayObject $log): DataImporter
    {
        // each anonymous class declaration is a distinct class, dependencies are given by class name
        return match ($name) {
            'lines' => new class($name, $dependencies, $log) extends RecordingImporter {},
            'stops' => new class($name, $dependencies, $log) extends RecordingImporter {},
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
    public function isOutdated(): bool
    {
        return true;
    }

    #[\Override]
    public function getDependencies(): array
    {
        return $this->dependencies;
    }

    #[\Override]
    public function getPriority(): int
    {
        return 0;
    }

    #[\Override]
    public function getDescription(): string
    {
        return $this->name;
    }
}
