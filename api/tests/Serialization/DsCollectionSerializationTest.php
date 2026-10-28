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

namespace App\Tests\Serialization;

use App\Dto\Status\Endpoint;
use App\Tests\Serialization\Fixture\GroupedValue;
use App\Tests\Serialization\Fixture\SeqEndpoints;
use Ds\Map;
use Ds\Seq;
use Nelmio\ApiDocBundle\Model\Model;
use Nelmio\ApiDocBundle\Model\ModelRegistry;
use OpenApi\Annotations\OpenApi;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\TypeInfo\Type;

final class DsCollectionSerializationTest extends KernelTestCase
{
    private SerializerInterface $serializer;

    #[\Override]
    protected function setUp(): void
    {
        self::bootKernel();

        $this->serializer = self::getContainer()->get(SerializerInterface::class);
    }

    public function testSeqNormalizesToList(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '[1,2,3]',
            $this->serializer->serialize(new Seq([1, 2, 3]), 'json')
        );
    }

    public function testMapNormalizesToObject(): void
    {
        $this->assertJsonStringEqualsJsonString(
            '{"a":1,"b":2}',
            $this->serializer->serialize(new Map([
                'a' => 1,
                'b' => 2,
            ]), 'json')
        );
    }

    public function testMapOfDtosPassesSerializationGroupsToElements(): void
    {
        $map = new Map([
            'x' => new GroupedValue('a', 'b'),
        ]);

        $this->assertJsonStringEqualsJsonString(
            '{"x":{"name":"a"}}',
            $this->serializer->serialize($map, 'json')
        );

        $this->assertJsonStringEqualsJsonString(
            '{"x":{"name":"a","extra":"b"}}',
            $this->serializer->serialize($map, 'json', [AbstractNormalizer::GROUPS => ['extra']])
        );
    }

    public function testDtoWithSeqPropertyRoundTrips(): void
    {
        $dto = new SeqEndpoints(new Seq([
            Endpoint::createFromArray([
                'name'     => 'a',
                'template' => '/a',
                'version'  => '1.0',
                'methods'  => ['GET'],
            ]),
            Endpoint::createFromArray([
                'name'     => 'b',
                'template' => '/b',
                'version'  => '1.1',
                'methods'  => ['GET', 'POST'],
            ]),
        ]));

        $json         = $this->serializer->serialize($dto, 'json');
        $deserialized = $this->serializer->deserialize($json, SeqEndpoints::class, 'json');

        // assertEquals() can't see inside a Seq (ext-ds objects have no properties), so compare the elements.
        $this->assertInstanceOf(Seq::class, $deserialized->getEndpoints());
        $this->assertContainsOnlyInstancesOf(Endpoint::class, $deserialized->getEndpoints());
        $this->assertEquals($dto->getEndpoints()->toArray(), $deserialized->getEndpoints()->toArray());
        $this->assertSame('b', $deserialized->getEndpoints()->toArray()[1]->getName());
        $this->assertSame(['GET', 'POST'], $deserialized->getEndpoints()->toArray()[1]->getMethods());
    }

    public function testDescriberDocumentsSeqLikeCollection(): void
    {
        // Schema produced for an unannotated `Collection<Endpoint>` property before the migration.
        $collectionSchema = [
            'oneOf' => [
                [
                    'type'  => 'array',
                    'items' => ['$ref' => '#/components/schemas/Endpoint'],
                ],
                [
                    'type'                 => 'object',
                    'additionalProperties' => ['$ref' => '#/components/schemas/Endpoint'],
                ],
            ],
        ];

        $schemas = $this->describe(SeqEndpoints::class);

        $this->assertSame($collectionSchema, $schemas['SeqEndpoints']['properties']['endpoints']);
        $this->assertSame(['Endpoint', 'SeqEndpoints'], array_keys($schemas));
    }

    private function describe(string $class): array
    {
        $generator  = self::getContainer()->get('nelmio_api_doc.generator.default');
        $describers = new \ReflectionProperty($generator, 'modelDescribers')->getValue($generator);

        $api      = new OpenApi([]);
        $registry = new ModelRegistry($describers, $api);
        $registry->register(new Model(Type::object($class)));
        $registry->registerSchemas();

        return json_decode($api->toJson(), true)['components']['schemas'];
    }
}
