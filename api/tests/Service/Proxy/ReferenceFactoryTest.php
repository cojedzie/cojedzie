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

declare(strict_types=1);

namespace App\Tests\Service\Proxy;

use App\Dto\JustReference;
use App\Dto\Stop;
use App\Service\Proxy\ReferenceFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class ReferenceFactoryTest extends KernelTestCase
{
    private ReferenceFactory $factory;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->factory = self::getContainer()->get(ReferenceFactory::class);
    }

    public function testCreatesReferenceSubclass(): void
    {
        $reference = $this->factory->get(Stop::class, '123');

        $this->assertInstanceOf(Stop::class, $reference);
        $this->assertInstanceOf(JustReference::class, $reference);
        $this->assertSame('123', $reference->getId());
    }

    public function testReferenceSerialisesToIdAndTypeOnly(): void
    {
        $serializer = self::getContainer()->get(SerializerInterface::class);

        $this->assertJsonStringEqualsJsonString(
            '{"id":"123","$type":"vnd.cojedzie.stop"}',
            $serializer->serialize($this->factory->get(Stop::class, '123'), 'json')
        );
    }
}
