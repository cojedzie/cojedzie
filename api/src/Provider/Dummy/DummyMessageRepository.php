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

namespace App\Provider\Dummy;

use App\Model\Message;
use App\Model\Stop;
use App\Provider\MessageRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DummyMessageRepository implements MessageRepository
{
    public function getAll(): Collection
    {
        return collect([
            Message::TYPE_INFO,
            Message::TYPE_UNKNOWN,
            Message::TYPE_BREAKDOWN
        ])->map(fn($type) => Message::createFromArray([
            'message'   => 'Lorem ipsum dolor sit amet.',
            'type'      => $type,
            'validFrom' => Carbon::now(),
            'validTo'   => Carbon::now()->addHour()
        ]));
    }

    public function getForStop(Stop $stop): Collection
    {
        return $this->getAll();
    }
}
