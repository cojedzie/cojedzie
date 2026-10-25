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

use App\Dto\Links\Link;
use App\Dto\Links\PaginationLinks;
use App\Serialization\Serializer;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\DataCollector\SerializerDataCollector;
use Symfony\Component\Serializer\Debug\TraceableSerializer;
use Symfony\Component\Serializer\SerializerInterface;

final class LinksSerializationTest extends KernelTestCase
{
    private SerializerInterface $serializer;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }

    public function testGetMethodIsOmittedFromLink(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"href":"/a"}',
            $this->serializer->serialize(new Link('/a'), 'json')
        );
    }

    public function testNonGetMethodIsKeptInLink(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"href":"/a","method":"POST"}',
            $this->serializer->serialize(new Link('/a', 'POST'), 'json')
        );
    }

    public function testMissingLinksAreSkipped(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"self":{"href":"/a"},"next":{"href":"/b"}}',
            $this->serializer->serialize(new PaginationLinks(self: new Link('/a'), next: new Link('/b')), 'json')
        );
    }

    public function testSerializerCanBeTracedByProfiler(): void
    {
        $traced = new TraceableSerializer(
            self::getContainer()->get(Serializer::class),
            new SerializerDataCollector(),
        );

        $this->assertJsonStringEqualsJsonString('{"href":"/a"}', $traced->serialize(new Link('/a'), 'json'));
    }
}
