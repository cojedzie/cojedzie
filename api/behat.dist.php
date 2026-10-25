<?php

declare(strict_types=1);

use App\Kernel;
use App\Tests\Behat\Context\ApiContext;
use App\Tests\Behat\Context\StopContext;
use Behat\Config\Config;
use Behat\Config\Extension;
use Behat\Config\Profile;
use Behat\Config\Suite;
use Behat\Config\TesterOptions;
use DAMA\DoctrineTestBundle\Behat\ServiceContainer\DoctrineExtension;
use FriendsOfBehat\SymfonyExtension\ServiceContainer\SymfonyExtension;

return (new Config())
    ->withProfile(
        (new Profile('default'))
            ->withTesterOptions((new TesterOptions())->withStrictResultInterpretation())
            ->withSuite(
                (new Suite('default'))
                    ->withPaths('%paths.base%/features')
                    ->withContexts(
                        ApiContext::class,
                        StopContext::class,
                    )
            )
            ->withExtension(new Extension(SymfonyExtension::class, [
                'bootstrap' => 'tests/bootstrap.php',
                'kernel'    => [
                    'class'       => Kernel::class,
                    'environment' => 'test',
                ],
            ]))
            ->withExtension(new Extension(DoctrineExtension::class))
    );
