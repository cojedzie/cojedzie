<?php
/*
 * Copyright (C) 2026 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */
declare(strict_types=1);

namespace App\Tests\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class FederationCommandsTest extends KernelTestCase
{
    public static function commands(): iterable
    {
        foreach ([
            'federation:check',
            'federation:cleanup',
            'federation:connect',
            'federation:connections:describe',
            'federation:connections:list',
            'federation:disconnect',
            'federation:register',
            'federation:servers',
        ] as $name) {
            yield $name => [$name];
        }
    }

    #[DataProvider('commands')]
    public function testCommandCanBeLoaded(string $name): void
    {
        $application = new Application(self::bootKernel());

        $command = $application->find($name);

        $this->assertSame($name, $command->getName());
        $this->assertNotEmpty($command->getDescription());
        $this->assertNotEmpty($command->getHelp() . $command->getSynopsis());
    }

    /**
     * An allowed URL without a host would let the server's connections point anywhere.
     */
    #[DataProvider('urlsWithoutHost')]
    public function testRegisterRefusesAnAllowedUrlWithoutHost(string $url): void
    {
        $tester = new CommandTester(new Application(self::bootKernel())->find('federation:register'));

        $status = $tester->execute([
            'email'       => 'maintainer@example.com',
            'allowed-url' => $url,
        ]);

        $this->assertSame(Command::INVALID, $status);
        $this->assertStringNotContainsString('Server Secret', $tester->getDisplay());
    }

    public static function urlsWithoutHost(): iterable
    {
        yield 'scheme only' => ['https://'];
        yield 'broken scheme' => ['http:/'];
        yield 'not a url' => ['node.example'];
        yield 'not http' => ['ftp://node.example'];
    }
}
