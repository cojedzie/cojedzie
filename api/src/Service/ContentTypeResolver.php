<?php

namespace App\Service;

use App\Dto\ContentType;
use App\Dto\DynamicContentType;
use ReflectionClass;

class ContentTypeResolver
{
    public function getContentType(mixed $value): ?string
    {
        if (is_object($value)) {
            return $this->getContentTypeForObject($value);
        }

        if (is_string($value) && class_exists($value)) {
            return $this->getContentTypeForClass($value);
        }

        return null;
    }

    public function getContentTypeForObject(object $object): ?string
    {
        if ($object instanceof DynamicContentType) {
            return $object->getContentType($this);
        }

        return $this->getContentTypeForClass($object::class);
    }

    public function getContentTypeForClass(string $class): ?string
    {
        $reflection = new ReflectionClass($class);
        $attributes = [];

        do {
            $attributes = [
                ...$attributes,
                ...$reflection->getAttributes(ContentType::class, \ReflectionAttribute::IS_INSTANCEOF),
            ];
        } while ($reflection = $reflection->getParentClass());

        if (empty($attributes)) {
            return null;
        }

        /** @var ContentType $contentType */
        $contentType = reset($attributes)->newInstance();

        return $contentType->type;
    }
}
