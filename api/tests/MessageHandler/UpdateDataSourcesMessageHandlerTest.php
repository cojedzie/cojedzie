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

namespace App\Tests\MessageHandler;

use App\DataImport\ImportGraph;
use App\Entity\ImportEntity;
use App\Message\UpdateDataSources;
use App\MessageHandler\UpdateDataSourcesMessageHandler;
use Monolog\Handler\TestHandler;
use Monolog\Logger;
use PHPUnit\Framework\TestCase;

final class UpdateDataSourcesMessageHandlerTest extends TestCase
{
    private TestHandler $logs;

    public function testStartsTheImport(): void
    {
        $graph = $this->createMock(ImportGraph::class);
        $graph->expects($this->once())->method('start')->willReturn(new ImportEntity());

        $this->handle($graph);

        $this->assertSame([], $this->logs->getRecords());
    }

    public function testImportThatFailsToStartIsLoggedInsteadOfRetried(): void
    {
        $graph = $this->createStub(ImportGraph::class);
        $graph->method('start')->willThrowException(new \LogicException('Importers depend on missing importers'));

        $this->handle($graph);

        $this->assertTrue($this->logs->hasCritical('Importers depend on missing importers'));
    }

    private function handle(ImportGraph $graph): void
    {
        $this->logs = new TestHandler();

        $handler = new UpdateDataSourcesMessageHandler($graph);
        $handler->setLogger(new Logger('test', [$this->logs]));
        $handler(new UpdateDataSources());
    }
}
