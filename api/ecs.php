<?php

declare(strict_types=1);

use PhpCsFixer\Fixer\ArrayNotation\ArraySyntaxFixer;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symplify\EasyCodingStandard\ValueObject\Option;
use Symplify\EasyCodingStandard\ValueObject\Set\SetList;

return static function (ContainerConfigurator $containerConfigurator): void {
    $parameters = $containerConfigurator->parameters();
    $parameters->set(Option::PATHS, [
        __DIR__.'/src',
        __DIR__.'/tests',
    ]);

    $services = $containerConfigurator->services();

    // run and fix, one by one
    $containerConfigurator->import(SetList::SPACES);
    $containerConfigurator->import(SetList::ARRAY);
    $containerConfigurator->import(SetList::DOCBLOCK);
    $containerConfigurator->import(SetList::PSR_12);

    $services->remove(\PhpCsFixer\Fixer\Operator\NotOperatorWithSuccessorSpaceFixer::class);
    $services->remove(\PhpCsFixer\Fixer\ClassNotation\SingleTraitInsertPerStatementFixer::class);
    $services->remove(\PhpCsFixer\Fixer\Casing\ConstantCaseFixer::class);
    $services->remove(\Symplify\CodingStandard\Fixer\ArrayNotation\ArrayOpenerAndCloserNewlineFixer::class);

    $services->set(ArraySyntaxFixer::class)
        ->call('configure', [
            [
                'syntax' => 'short',
            ],
        ]);

    $services
        ->set(\PhpCsFixer\Fixer\Operator\BinaryOperatorSpacesFixer::class)
        ->call('configure', [
            [
                'operators' => [
                    '=>' => 'align_single_space_minimal',
                    '='  => 'align_single_space_minimal',
                ],
            ],
        ]);

    $services
        ->set(\PhpCsFixer\Fixer\ClassNotation\ClassAttributesSeparationFixer::class)
        ->call('configure', [
            [
                'elements' => [
                    'method' => 'one',
                    'property' => 'only_if_meta',
                ],
            ],
        ]);

    $services->set(\PhpCsFixer\Fixer\Import\NoUnusedImportsFixer::class);
};
