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

namespace App\Tests\Provider;

use App\Dto\Message;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Filter\Requirement\Requirement;
use App\Provider\InMemory\InMemoryRepository;
use App\Provider\MessageRepository;
use App\Service\HandlerProviderFactory;
use Ds\Seq;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * A repository's first() returns null when nothing matches, callers turn that into "not found".
 */
final class RepositoryFirstReturnsNullTest extends KernelTestCase
{
    public function testInMemoryRepositoryFirstReturnsNullWhenNothingMatches(): void
    {
        $repository = $this->inMemoryMessages();
        $noMatch    = new FieldFilter('type', Message::TYPE_BREAKDOWN, FieldFilterOperator::Equals);

        $this->assertNull($repository->first($noMatch));
    }

    public function testInMemoryRepositoryFirstReturnsTheFirstMatch(): void
    {
        $repository = $this->inMemoryMessages();
        $info       = new FieldFilter('type', Message::TYPE_INFO, FieldFilterOperator::Equals);

        $all = $repository->all($info);

        $this->assertSame(['first', 'third'], $all->map(fn (Message $message) => $message->getId())->toArray());
        $this->assertSame('first', $repository->first($info)->getId());
    }

    private function inMemoryMessages(): MessageRepository
    {
        self::bootKernel();

        $messages = new Seq([
            Message::createFromArray([
                'id'      => 'first',
                'type'    => Message::TYPE_INFO,
                'message' => 'a',
            ]),
            Message::createFromArray([
                'id'      => 'second',
                'type'    => Message::TYPE_UNKNOWN,
                'message' => 'b',
            ]),
            Message::createFromArray([
                'id'      => 'third',
                'type'    => Message::TYPE_INFO,
                'message' => 'c',
            ]),
        ]);

        return new class(self::getContainer()->get(HandlerProviderFactory::class), $messages) extends InMemoryRepository implements MessageRepository {
            public function __construct(
                HandlerProviderFactory $handlerProviderFactory,
                private readonly Seq $messages
            ) {
                parent::__construct($handlerProviderFactory);
            }

            #[\Override]
            public function all(Requirement ...$requirements): Seq
            {
                return $this->filterAndProcessResults($this->messages, $requirements);
            }
        };
    }
}
