<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\PHPUnit\Set\PHPUnitSetList;
use Rector\Symfony\Set\SymfonySetList;

return RectorConfig::configure()
    ->withPaths([__DIR__ . '/src', __DIR__ . '/tests'])
    ->withImportNames(importShortClasses: false, removeUnusedImports: true)
    ->withParallel()
    ->withPhpSets()
    ->withComposerBased(symfony: true, doctrine: true, phpunit: true)
    ->withAttributesSets(symfony: true, doctrine: true)
    ->withSets([
        SymfonySetList::SYMFONY_CODE_QUALITY,
        PHPUnitSetList::PHPUNIT_CODE_QUALITY,
    ]);
