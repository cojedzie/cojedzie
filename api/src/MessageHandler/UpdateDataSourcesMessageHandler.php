<?php
/*
 * Copyright (C) 2021 Kacper Donat
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

namespace App\MessageHandler;

use App\DataImport\ImportGraph;
use App\Message\UpdateDataSources;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Starts the import; workers run its tasks. Its check-ins are reported by ImportCronMonitorSubscriber.
 */
#[AsMessageHandler]
final class UpdateDataSourcesMessageHandler implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly ImportGraph $graph,
    ) {
    }

    public function __invoke(UpdateDataSources $message): void
    {
        try {
            $this->graph->start();
        } catch (\Throwable $exception) {
            $this->logger->critical($exception->getMessage(), [
                'backtrace' => $exception->getTraceAsString(),
            ]);
        }
    }
}
