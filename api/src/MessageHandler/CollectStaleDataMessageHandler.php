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

namespace App\MessageHandler;

use App\DataImport\StaleDataCollector;
use App\Message\CollectStaleData;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final readonly class CollectStaleDataMessageHandler
{
    public function __construct(
        private StaleDataCollector $collector,
        #[Autowire(service: 'app.import.logger')]
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(CollectStaleData $message): void
    {
        $removed = $this->collector->collect();

        if ($removed === []) {
            $this->logger->warning('No import finished within the retention period, stale data was not collected.');
            return;
        }

        $this->logger->info('Collected stale data.', ['removed' => $removed]);
    }
}
