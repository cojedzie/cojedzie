<?php
/*
 * Copyright (C) 2022 Kacper Donat
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

namespace App\Parser\Consumer;

trait ConsumerHelpersTrait
{
    public function map(callable $transform): ConsumerInterface
    {
        return new TransformedConsumer(
            $this,
            $transform
        );
    }

    public function reduce(callable $reducer): ConsumerInterface
    {
        return new ReducedConsumer(
            $this,
            $reducer
        );
    }

    public function optional(): ConsumerInterface
    {
        return Consumer::optional($this);
    }

    public function repeated(): ConsumerInterface
    {
        return Consumer::many($this);
    }

    public function ignore(): ConsumerInterface
    {
        return Consumer::ignore($this);
    }
}
