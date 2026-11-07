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

namespace App\Tests\MessageHandler;

use App\DataImport\DataImporter;
use App\DataImport\ImportGraph;
use App\DataImport\ImportTaskRunner;
use App\DataImport\ProgressReporterFactory;
use App\DataImport\ProgressReporterInterface;
use App\Event\DataUpdateEvent;
use App\Message\RunImportTask;
use App\MessageHandler\RunImportTaskMessageHandler;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\NullLogger;
use Sentry\CheckInStatus;
use Sentry\MonitorConfig;
use Sentry\State\HubInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Clock\MockClock;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;

final class RunImportTaskMessageHandlerTest extends KernelTestCase
{
    private ImportTaskRunner $runner;

    public function testCheckInIsReportedOnceWhenTheImportFinishes(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        $em->getConnection()->beginTransaction();

        try {
            $importer = fn () => new class() implements DataImporter {
                #[\Override]
                public function import(ProgressReporterInterface $reporter, DataUpdateEvent $event): void
                {
                }

                #[\Override]
                public function getDependencies(): array
                {
                    return [];
                }

                #[\Override]
                public function getDescription(): string
                {
                    return 'nothing';
                }
            };

            $locator = new ServiceLocator(['lines' => $importer, 'stops' => $importer]);
            /** @var \ArrayObject<int, RunImportTask> $messages */
            $messages = new \ArrayObject();
            $bus      = $this->createStub(MessageBusInterface::class);
            $bus->method('dispatch')->willReturnCallback(function (RunImportTask $message) use ($messages) {
                $messages[] = $message;

                return new Envelope($message);
            });

            $clock        = new MockClock('2026-10-28 12:00:00');
            $graph        = new ImportGraph($em, $locator, $bus, $clock, fn () => $this->runner);
            $this->runner = new ImportTaskRunner($em, $locator, $graph, new ProgressReporterFactory(new NullLogger()), $clock);

            /** @var \ArrayObject<int, array{string, CheckInStatus, ?string}> $checkIns */
            $checkIns = new \ArrayObject();
            $hub      = $this->createStub(HubInterface::class);
            $hub->method('captureCheckIn')->willReturnCallback(
                function (string $slug, CheckInStatus $status, $duration = null, ?MonitorConfig $config = null, ?string $id = null) use ($checkIns): string {
                    $checkIns[] = [$slug, $status, $id];

                    return 'other-id';
                }
            );

            $handler = new RunImportTaskMessageHandler($this->runner);
            $handler->setSentryHub($hub);

            $graph->start('check-in-id');
            $this->assertCount(2, $messages);

            $handler($messages[0]);
            $this->assertSame([], $checkIns->getArrayCopy());

            // the last task, delivered twice
            $handler($messages[1]);
            $handler($messages[1]);
            $this->assertSame([['update-data-sources', CheckInStatus::ok(), 'check-in-id']], $checkIns->getArrayCopy());
        } finally {
            $em->getConnection()->rollBack();
        }
    }
}
