<?php

namespace App\Service\Proxy;

use ProxyManager\Factory\AbstractBaseFactory;
use ProxyManager\ProxyGenerator\ProxyGeneratorInterface;

class ReferenceObjectFactory extends AbstractBaseFactory
{
    public function get($class, $id)
    {
        $id = is_array($id) ? $id : compact('id');

        $proxy = $this->generateProxy($class);

        $object = new $proxy();
        $object->fill($id);
        return $object;
    }

    protected function getGenerator(): ProxyGeneratorInterface
    {
        return new ReferenceObjectGenerator();
    }
}