<?php

declare(strict_types=1);

use Rector\Core\Configuration\Option;
use Rector\Doctrine\Set\DoctrineSetList;
use Rector\Set\ValueObject\LevelSetList;
use Rector\Symfony\Set\SensiolabsSetList;
use Rector\Symfony\Set\SymfonySetList;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return static function (ContainerConfigurator $containerConfigurator): void {
    // get parameters
    $parameters = $containerConfigurator->parameters();
    $services = $containerConfigurator->services();

    $parameters->set(Option::PATHS, [__DIR__.'/src']);
    $parameters->set(Option::AUTO_IMPORT_NAMES, true);
    $parameters->set(Option::IMPORT_SHORT_CLASSES, false);

    // Define what rule sets will be applied
    $containerConfigurator->import(LevelSetList::UP_TO_PHP_81);

    $containerConfigurator->import(DoctrineSetList::ANNOTATIONS_TO_ATTRIBUTES);
    $containerConfigurator->import(SymfonySetList::ANNOTATIONS_TO_ATTRIBUTES);
    $containerConfigurator->import(SensiolabsSetList::FRAMEWORK_EXTRA_61);
};
