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

namespace App\DataConverter;

use Ds\Seq;
use Kadet\Functional\Collections as c;
use function Kadet\Functional\Predicates\instance;

class AggregateConverter implements Converter, CacheableConverter
{
    /**
     * @var Seq<Converter>|null
     */
    private ?Seq $cachedConverters = null;

    public function __construct(
        private readonly iterable $converters
    ) {
    }

    #[\Override]
    public function convert($entity, string $type)
    {
        $this->ensureCachedConverters();

        /** @var Converter $converter */
        $converter = $this->cachedConverters |> c\first(fn (Converter $converter) => $converter->supports($entity, $type));

        if ($converter == null) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot convert entity of type %s into %s.',
                get_debug_type($entity),
                $type,
            ));
        }

        return $converter->convert($entity, $type);
    }

    #[\Override]
    public function supports($entity, string $type)
    {
        $this->ensureCachedConverters();

        return $this->cachedConverters |> c\any(fn (Converter $converter) => $converter->supports($entity, $type));
    }

    /**
     * @return Seq<Converter>
     */
    public function getConverters(): Seq
    {
        $this->ensureCachedConverters();

        return $this->cachedConverters->copy();
    }

    #[\Override]
    public function reset(): void
    {
        $this->ensureCachedConverters();

        $this->cachedConverters
            |> c\filter(instance(CacheableConverter::class))
            |> c\each(function (CacheableConverter $converter) {
                $converter->reset();
            });
    }

    public function __clone()
    {
        $this->ensureCachedConverters();

        $this->cachedConverters = $this->cachedConverters |> c\map(fn ($object) => clone $object);
    }

    private function ensureCachedConverters()
    {
        if (!$this->cachedConverters) {
            $this->cachedConverters = $this->converters
                |> c\filter(fn (Converter $converter) => $converter !== $this && !$converter instanceof AggregateConverter)
                |> c\each(function (Converter $converter) {
                    if ($converter instanceof RecursiveConverter) {
                        $converter->setParent($this);
                    }
                });
        }
    }
}
