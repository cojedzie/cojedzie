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
use App\Message\UpdateDataSources;
use App\Repository\FederatedConnectionEntityRepository;
use App\Schedule\DefaultScheduleProvider;
use App\Schedule\HubScheduleProvider;
use App\Schedule\ScheduleConfig;
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
        foreach (self::getContainer()->get(DefaultScheduleProvider::class)->getSchedule()->getRecurringMessages() as $recurring) {
            $messages = [...$messages, ...$recurring->getProvider()->getMessages(new MessageContext('default', $recurring->getId(), $recurring->getTrigger(), new \DateTimeImmutable()))];
        }

        $this->assertCount(2, $messages);
        $this->assertContainsOnlyInstancesOf(RedispatchMessage::class, $messages);
        $this->assertInstanceOf(UpdateDataSources::class, $messages[0]->envelope);
        $this->assertInstanceOf(CollectStaleData::class, $messages[1]->envelope);

        foreach ($messages as $message) {
            foreach ((array) $message->transportNames as $transport) {
                $this->assertTrue(self::getContainer()->has('messenger.transport.' . $transport), sprintf('Transport "%s" does not exist', $transport));
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
        );

        $hub = new HubScheduleProvider($this->createStub(FederatedConnectionEntityRepository::class), $config);

        $this->assertSame(['30 2 * * *', '45 2 * * *'], self::triggers(new DefaultScheduleProvider($config)->getSchedule()));
        $this->assertSame(['*/5 * * * *', '0 3 * * 0'], self::triggers($hub->getSchedule()));
    }

    public function testSchedulesDefaultToTheProductionTimes(): void
    {
        self::bootKernel();

        $this->assertSame(['0 5 * * *', '0 6 * * *'], self::triggers(self::getContainer()->get(DefaultScheduleProvider::class)->getSchedule()));
        $this->assertSame(['* * * * *', '0 0 * * *'], self::triggers(self::getContainer()->get(HubScheduleProvider::class)->getSchedule()));
    }

    public function testDefaultJobsAreSentToTheWorker(): void
    {
        self::bootKernel();

        /** @var SendersLocatorInterface $senders */
        $senders = self::getContainer()->get('messenger.senders_locator');

        $this->assertSame(['main'], array_keys(iterator_to_array($senders->getSenders(new Envelope(new UpdateDataSources())))));
        $this->assertSame(['main'], array_keys(iterator_to_array($senders->getSenders(new Envelope(new CollectStaleData())))));
    }

    public function testScheduledMessagesHaveHandlers(): void
    {
        self::bootKernel();

        /** @var HandlersLocatorInterface $handlers */
        $handlers = self::getContainer()->get('messenger.bus.default.messenger.handlers_locator');

        $this->assertNotEmpty(iterator_to_array($handlers->getHandlers(new Envelope(new CleanupFederatedConnections()))));
        $this->assertNotEmpty(iterator_to_array($handlers->getHandlers(new Envelope(new CollectStaleData()))));
    }

    /**
     * @return list<string>
     */
    private static function triggers(Schedule $schedule): array
    {
        return array_map(fn (RecurringMessage $message) => (string) $message->getTrigger(), array_values($schedule->getRecurringMessages()));
    }
}
