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

namespace App\Parser\StreamingParser;

use App\Parser\StreamingParserInterface;
use JetBrains\PhpStorm\Pure;

trait StreamingParserCombinatorsTrait
{
    #[Pure]
    public function map(callable $transform): StreamingParserInterface
    {
        return new TransformedStreamingParser(
            $this,
            $transform
        );
    }

    #[Pure]
    public function reduce(callable $reducer): self
    {
        return new ReducedStreamingParser(
            $this,
            $reducer
        );
    }

    #[Pure]
    public function optional(): StreamingParserInterface
    {
        return StreamingParser::optional($this);
    }

    #[Pure]
    public function repeated(): StreamingParserInterface
    {
        return StreamingParser::many($this);
    }

    #[Pure]
    public function ignore(): StreamingParserInterface
    {
        return StreamingParser::ignore($this);
    }
}
