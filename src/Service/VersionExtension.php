<?php

namespace App\Service;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class VersionExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('version', function () {
                return substr(`git rev-parse HEAD`, 0, 8) ?: '1.0-dev';
            })
        ];
    }
}