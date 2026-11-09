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

namespace App\Tests\Schedule;

use App\Message\CleanupFederatedConnections;
use App\Message\CollectStaleData;
use App\Message\RecordDepartures;
use App\Message\UpdateDataSources;
use App\Repository\FederatedConnectionEntityRepository;
use App\Schedule\DefaultScheduleProvider;
use App\Schedule\HubScheduleProvider;
use App\Schedule\RecorderScheduleProvider;
use App\Schedule\ScheduleConfig;
use App\Tests\DepartureHistory\Support\InMemoryDepartureHistory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Handler\HandlersLocatorInterface;
use Symfony\Component\Messenger\Message\RedispatchMessage;
use Symfony\Component\Messenger\Transport\Sender\SendersLocatorInterface;
use Symfony\Component\Scheduler\Generator\MessageContext;
use Symfony\Component\Scheduler\RecurringMessage;
use Symfony\Component\Scheduler\Schedule;

final class ScheduledMessagesTest extends KernelTestCase
{
    public function testDefaultJobsAreRedispatchedToAnExistingTransport(): void
    {
        self::bootKernel();

        $messages = [];
        $schedule = self::getContainer()->get(DefaultScheduleProvider::class)->getSchedule();
        foreach ($schedule->getRecurringMessages() as $recurring) {
            $context = new MessageContext(
                'default',
                $recurring->getId(),
                $recurring->getTrigger(),
                new \DateTimeImmutable(),
            );
            $messages = [...$messages, ...$recurring->getProvider()->getMessages($context)];
        }

        $this->assertCount(2, $messages);
        $this->assertContainsOnlyInstancesOf(RedispatchMessage::class, $messages);
        $this->assertInstanceOf(UpdateDataSources::class, $messages[0]->envelope);
        $this->assertInstanceOf(CollectStaleData::class, $messages[1]->envelope);

        foreach ($messages as $message) {
            foreach ((array) $message->transportNames as $transport) {
                $this->assertTrue(
                    self::getContainer()->has('messenger.transport.' . $transport),
                    sprintf('Transport "%s" does not exist', $transport),
                );
            }
        }
    }

    public function testSchedulesFollowTheirConfiguration(): void
    {
        $config = new ScheduleConfig(
            updateDataSources: '30 2 * * *',
            federationCheck: '*/5 * * * *',
            federationCleanup: '0 3 * * 0',
            collectStaleData: '45 2 * * *',
            recordDepartures: '30 seconds',
        );

        $default  = new DefaultScheduleProvider($config);
        $hub      = new HubScheduleProvider($this->createStub(FederatedConnectionEntityRepository::class), $config);
        $recorder = new RecorderScheduleProvider(self::history(), $config);

        $this->assertSame(['30 2 * * *', '45 2 * * *'], self::triggers($default->getSchedule()));
        $this->assertSame(['*/5 * * * *', '0 3 * * 0'], self::triggers($hub->getSchedule()));
        $this->assertSame(['every 30 seconds'], self::triggers($recorder->getSchedule()));
    }

    public function testRecorderRunsOnlyWithClickHouse(): void
    {
        $config = new ScheduleConfig('0 5 * * *', '* * * * *', '0 0 * * *', '0 6 * * *', '20 seconds');

        $disabled = new RecorderScheduleProvider(self::history(false), $config);
        $enabled  = new RecorderScheduleProvider(self::history(), $config);

        $this->assertSame([], self::triggers($disabled->getSchedule()));

        $recurring = array_values($enabled->getSchedule()->getRecurringMessages());
        $this->assertCount(1, $recurring);
        $context = new MessageContext(
            'recorder',
            $recurring[0]->getId(),
            $recurring[0]->getTrigger(),
            new \DateTimeImmutable(),
        );
        $messages = [...$recurring[0]->getProvider()->getMessages($context)];
        $this->assertCount(1, $messages);
        $this->assertInstanceOf(RecordDepartures::class, $messages[0]);
    }

    public function testSchedulesDefaultToTheProductionTimes(): void
    {
        self::bootKernel();

        $default = self::getContainer()->get(DefaultScheduleProvider::class);
        $hub     = self::getContainer()->get(HubScheduleProvider::class);

        $this->assertSame(['0 5 * * *', '0 6 * * *'], self::triggers($default->getSchedule()));
        $this->assertSame(['* * * * *', '0 0 * * *'], self::triggers($hub->getSchedule()));
        $this->assertSame('20 seconds', self::getContainer()->get(ScheduleConfig::class)->recordDepartures);
    }

    public function testDefaultJobsAreSentToTheWorker(): void
    {
        self::bootKernel();

        /** @var SendersLocatorInterface $senders */
        $senders = self::getContainer()->get('messenger.senders_locator');

        $sendersOf = fn (object $message) => array_keys(
            iterator_to_array($senders->getSenders(new Envelope($message))),
        );

        $this->assertSame(['main'], $sendersOf(new UpdateDataSources()));
        $this->assertSame(['main'], $sendersOf(new CollectStaleData()));
    }

    public function testScheduledMessagesHaveHandlers(): void
    {
        self::bootKernel();

        /** @var HandlersLocatorInterface $handlers */
        $handlers = self::getContainer()->get('messenger.bus.default.messenger.handlers_locator');

        $this->assertNotEmpty(
            iterator_to_array($handlers->getHandlers(new Envelope(new CleanupFederatedConnections()))),
        );
        $this->assertNotEmpty(iterator_to_array($handlers->getHandlers(new Envelope(new CollectStaleData()))));
        $this->assertNotEmpty(iterator_to_array($handlers->getHandlers(new Envelope(new RecordDepartures()))));
    }

    private static function history(bool $enabled = true): InMemoryDepartureHistory
    {
        return new InMemoryDepartureHistory($enabled);
    }

    /**
     * @return list<string>
     */
    private static function triggers(Schedule $schedule): array
    {
        return array_map(
            fn (RecurringMessage $message) => (string) $message->getTrigger(),
            array_values($schedule->getRecurringMessages()),
        );
    }
}
