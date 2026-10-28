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

use App\Dto\Message;
use App\Filter\Requirement\LimitConstraint;
use App\Filter\Requirement\Requirement;
use App\Provider\MessageRepository;
use Carbon\Carbon;
use Ds\Seq;
use Kadet\Functional\Collections as c;

class DummyMessageRepository implements MessageRepository
{
    #[\Override]
    public function all(Requirement ...$requirements): Seq
    {
        return new Seq([
            Message::TYPE_INFO,
            Message::TYPE_UNKNOWN,
            Message::TYPE_BREAKDOWN,
        ]) |> c\map(fn ($type) => Message::createFromArray([
            'message'   => 'Lorem ipsum dolor sit amet.',
            'type'      => $type,
            'validFrom' => Carbon::now(),
            'validTo'   => Carbon::now()->addHour(),
        ]));
    }

    #[\Override]
    public function first(Requirement ...$requirements)
    {
        return $this->all(LimitConstraint::count(1), ...$requirements) |> c\first();
    }
}
