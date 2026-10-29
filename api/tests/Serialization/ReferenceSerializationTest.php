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

namespace App\Tests\Serialization;

use App\Dto\Stop;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\SerializerInterface;

final class ReferenceSerializationTest extends KernelTestCase
{
    public function testReferenceSerialisesToIdAndTypeOnly(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"id":"123","$type":"vnd.cojedzie.stop"}',
            $this->serialize(Stop::reference('123'))
        );
    }

    public function testObjectWithTheSameIdSerialisesInFull(): void
    {
        $json = json_decode($this->serialize(Stop::createFromArray([
            'id'   => '123',
            'name' => 'Brama Wyżynna',
        ])), true);

        $this->assertSame('Brama Wyżynna', $json['name']);
    }

    private function serialize(object $object): string
    {
        return self::getContainer()->get(SerializerInterface::class)->serialize($object, 'json');
    }
}
