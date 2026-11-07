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

namespace App\MessageHandler;

use App\DataImport\ImportTaskRunner;
use App\Message\RunImportTask;
use App\Utility\SentryCronMonitorTrait;
use Sentry\CheckInStatus;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class RunImportTaskMessageHandler
{
    use SentryCronMonitorTrait;

    public function __construct(
        private readonly ImportTaskRunner $runner,
    ) {
    }

    public function __invoke(RunImportTask $message): void
    {
        $import = $this->runner->run($message->taskId);

        // the task that finished the import closes the check-in the scheduled update opened
        if ($import?->getCheckInId() !== null) {
            $this->sentry->captureCheckIn(
                UpdateDataSourcesMessageHandler::MONITOR,
                $import->isSucceeded() ? CheckInStatus::ok() : CheckInStatus::error(),
                checkInId: $import->getCheckInId(),
            );
        }
    }
}
