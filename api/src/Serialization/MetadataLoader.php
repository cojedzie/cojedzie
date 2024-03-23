<?php

namespace App\Serialization;

use Symfony\Component\Serializer\Mapping\ClassMetadataInterface;
use Symfony\Component\Serializer\Mapping\Loader\LoaderInterface;

class MetadataLoader implements LoaderInterface
{
    public function __construct(
        private readonly LoaderInterface $decorated
    ) {
    }

    public function loadClassMetadata(ClassMetadataInterface $classMetadata): bool
    {
        $loaded = $this->decorated->loadClassMetadata($classMetadata);

        foreach ($classMetadata->getAttributesMetadata() as $attributeMetadata) {
            if (empty($attributeMetadata->getGroups())) {
                $attributeMetadata->addGroup('default');
            }
        }

        return $loaded;
    }
}
