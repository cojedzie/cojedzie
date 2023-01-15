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

namespace App\Service;

use App\Serialization\SerializeAs;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use Metadata\AdvancedMetadataFactoryInterface;
use Metadata\ClassHierarchyMetadata;
use ReflectionAttribute;
use function Kadet\Functional\Transforms\property;

final class SerializerContextFactory
{
    public function __construct(
        private readonly AdvancedMetadataFactoryInterface $factory
    ) {
    }

    public function create($subject, array $groups = ['Default'])
    {
        return SerializationContext::create()
            ->setSerializeNull(true)
            ->setGroups($this->groups($subject, $groups));
    }

    private function groups($subject, array $groups)
    {
        $metadata   = $this->factory->getMetadataForClass(is_object($subject) ? $subject::class : $subject);
        $properties = $metadata instanceof ClassHierarchyMetadata
            ? collect($metadata->classMetadata)->flatMap(property('propertyMetadata'))
            : $metadata->propertyMetadata;

        $fields = [];
        /** @var PropertyMetadata $property */
        foreach ($properties as $property) {
            try {
                $annotation = $this->getAnnotationForProperty($property);
                if ($annotation && !empty($fieldGroups = $this->map($annotation, $groups))) {
                    $type  = $property->type;
                    $class = $type['name'] !== 'array' ? $type['name'] : $type['params'][0];

                    $fields[$property->name] = $this->groups($class, $fieldGroups);
                }
            } catch (\ReflectionException) {
            }
        }

        return array_merge($groups, $fields);
    }

    private function getAnnotationForProperty(PropertyMetadata $metadata)
    {
        $reflection = new \ReflectionClass($metadata->class);

        try {
            $property   = $reflection->getProperty($metadata->name);
            $attributes = $property->getAttributes(SerializeAs::class, \ReflectionAttribute::IS_INSTANCEOF);
        } catch (\ReflectionException) {
            $method     = $reflection->getMethod($metadata->getter);
            $attributes = $method->getAttributes(SerializeAs::class, \ReflectionAttribute::IS_INSTANCEOF);
        }

        if (!isset($attributes)) {
            return null;
        }

        /** @var SerializeAs[] $attributes */
        $attributes = array_map(fn (ReflectionAttribute $attribute) => $attribute->newInstance(), $attributes);

        if (count($attributes) === 1) {
            return reset($attributes);
        }

        $map = [];
        foreach ($attributes as $attribute) {
            $map = [...$map, ...$attribute->map];
        }

        return new SerializeAs(map: $map);
    }

    private function map(SerializeAs $annotation, array $groups)
    {
        $result = [];

        foreach ($groups as $group) {
            if (array_key_exists($group, $annotation->map)) {
                $result[] = $annotation->map[$group];
            } else {
                $result = [...$result, ...$groups];
            }
        }

        return $result;
    }
}
