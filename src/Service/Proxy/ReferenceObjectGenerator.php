<?php

namespace App\Service\Proxy;

use App\Model\JustReference;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;
use ReflectionClass;
use Zend\Code\Generator\ClassGenerator;

class ReferenceObjectGenerator implements ProxyGeneratorInterface
{
    public function generate(ReflectionClass $class, ClassGenerator $generator)
    {
        $interfaces = array_merge($class->getInterfaceNames(), [ JustReference::class ]);

        $generator->setExtendedClass($class->getName());
        $generator->setImplementedInterfaces($interfaces);
    }
}