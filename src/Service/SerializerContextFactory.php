<?php

namespace App\Service;

use App\Serialization\SerializeAs;
use Doctrine\Common\Annotations\Reader;
use JMS\Serializer\Metadata\PropertyMetadata;
use JMS\Serializer\SerializationContext;
use Metadata\AdvancedMetadataFactoryInterface;
use Metadata\ClassHierarchyMetadata;
use function Kadet\Functional\Transforms\property;

final class SerializerContextFactory
{
    private $factory;
    private $reader;

    public function __construct(AdvancedMetadataFactoryInterface $factory, Reader $reader)
    {
        $this->factory = $factory;
        $this->reader = $reader;
    }

    public function create($subject, array $groups)
    {
        return SerializationContext::create()->setGroups($this->groups($subject, $groups));
    }

    private function groups($subject, array $groups)
    {
        $metadata = $this->factory->getMetadataForClass(is_object($subject) ? get_class($subject) : $subject);
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
            } catch (\ReflectionException $e) { }
        }

        return array_merge($groups, $fields);
    }

    private function getAnnotationForProperty(PropertyMetadata $metadata)
    {
        $reflection = new \ReflectionClass($metadata->class);

        try {
            $property = $reflection->getProperty($metadata->name);
            /** @var SerializeAs $annotation */
            return $this->reader->getPropertyAnnotation($property, SerializeAs::class);
        } catch (\ReflectionException $exception) {
            $method = $reflection->getMethod($metadata->getter);
            return $this->reader->getMethodAnnotation($method, SerializeAs::class);
        }
    }

    private function map(SerializeAs $annotation, array $groups)
    {
        $result = [];

        foreach ($groups as $group) {
            if (array_key_exists($group, $annotation->map)) {
                $result[] = $annotation->map[$group];
            }
        }

        return $result;
    }
}
