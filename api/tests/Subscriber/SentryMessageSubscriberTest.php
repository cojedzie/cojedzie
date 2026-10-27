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

namespace App\Tests\Subscriber;

use App\Message\UpdateDataSources;
use App\Subscriber\SentryMessageSubscriber;
use PHPUnit\Framework\TestCase;
use Sentry\ClientBuilder;
use Sentry\Event;
use Sentry\EventType;
use Sentry\State\Hub;
use Sentry\Transport\Result;
use Sentry\Transport\ResultStatus;
use Sentry\Transport\TransportInterface;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\Event\WorkerMessageHandledEvent;
use Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent;

final class SentryMessageSubscriberTest extends TestCase
{
    public function testSendsTransactionOfHandledMessage(): void
    {
        $transport = new class() implements TransportInterface {
            /**
             * @var list<Event>
             */
            public array $sent = [];

            public function send(Event $event): Result
            {
                $this->sent[] = $event;

                return new Result(ResultStatus::success(), $event);
            }

            public function close(?int $timeout = null): Result
            {
                return new Result(ResultStatus::success());
            }
        };

        $client = ClientBuilder::create(['dsn' => 'https://public@sentry.invalid/1',
            'traces_sample_rate'               => 1.0])
            ->setTransport($transport)
            ->getClient()
        ;

        $subscriber = new SentryMessageSubscriber(new Hub($client));
        $envelope   = new Envelope(new UpdateDataSources());

        $subscriber->handleMessageReceived(new WorkerMessageReceivedEvent($envelope, 'main'));
        $subscriber->handleMessageDone(new WorkerMessageHandledEvent($envelope, 'main'));

        $this->assertCount(1, $transport->sent);
        $this->assertSame(EventType::transaction(), $transport->sent[0]->getType());
        $this->assertSame(UpdateDataSources::class, $transport->sent[0]->getTransaction());
    }
}
