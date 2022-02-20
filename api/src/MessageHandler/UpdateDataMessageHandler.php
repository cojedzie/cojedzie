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

use App\Message\UpdateDataMessage;
use App\Output\LoggerOutput;
use App\Service\DataUpdater;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

final class UpdateDataMessageHandler implements MessageHandlerInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly DataUpdater $updater)
    {
    }

    public function __invoke(UpdateDataMessage $message)
    {
        try {
            $this->updater->update(new LoggerOutput($this->logger));
        } catch (\Exception $exception) {
            $this->logger->critical($exception->getMessage(), [
                'backtrace' => $exception->getTraceAsString()
            ]);
        }
    }
}
