<?php

namespace App\Service\Proxy;

final class FileLocator extends \ProxyManager\FileLocator\FileLocator
{
    public function __construct(string $proxiesDirectory)
    {
        $absolutePath = realpath($proxiesDirectory);

        if ($absolutePath === false) {
            mkdir($proxiesDirectory, 0755, true);
        }

        parent::__construct($proxiesDirectory);
    }
}
