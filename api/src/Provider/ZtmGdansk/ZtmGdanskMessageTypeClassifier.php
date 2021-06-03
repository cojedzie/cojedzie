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

namespace App\Provider\ZtmGdansk;

use App\Model\Message;

class ZtmGdanskMessageTypeClassifier
{
    public function classify(Message $message): string
    {
        switch (true) {
            case preg_match('/(awari|opóźnie)/i', $message->getMessage()):
                return Message::TYPE_BREAKDOWN;

            case preg_match('#gdansk.pl/powietrze#i', $message->getMessage()):
                return false; // spam

            default:
                return Message::TYPE_INFO;
        }
    }
}
