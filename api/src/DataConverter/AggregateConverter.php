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

use Illuminate\Support\Collection;
use function collect;
use function Kadet\Functional\Predicates\instance;

class AggregateConverter implements Converter, CacheableConverter
{
    private $cachedConverters;

    public function __construct(private readonly iterable $converters)
    {
    }

    public function convert($entity, string $type)
    {
        $this->ensureCachedConverters();

        /** @var Converter $converter */
        $converter = $this->cachedConverters->first(fn (Converter $converter) => $converter->supports($entity, $type));

        if ($converter == null) {
            throw new \InvalidArgumentException(sprintf(
                'Cannot convert entity of type %s into %s.',
                is_object($entity) ? get_class($entity) : gettype($entity),
                $type,
            ));
        }

        return $converter->convert($entity, $type);
    }

    public function supports($entity, string $type)
    {
        $this->ensureCachedConverters();

        return $this
            ->cachedConverters
            ->some(fn (Converter $converter) => $converter->supports($entity, $type))
        ;
    }

    public function getConverters(): Collection
    {
        $this->ensureCachedConverters();

        return clone $this->cachedConverters;
    }

    public function reset()
    {
        $this->ensureCachedConverters();

        $this
            ->cachedConverters
            ->filter(instance(CacheableConverter::class))
            ->each(function (CacheableConverter $converter) {
                $converter->reset();
            })
        ;
    }

    public function __clone()
    {
        $this->ensureCachedConverters();

        $this->cachedConverters = $this->cachedConverters->map(fn ($object) => clone $object);
    }

    private function ensureCachedConverters()
    {
        if (!$this->cachedConverters) {
            $this->cachedConverters = collect($this->converters)
                ->filter(fn (Converter $converter) => $converter !== $this && !$converter instanceof AggregateConverter)
                ->each(function (Converter $converter) {
                    if ($converter instanceof RecursiveConverter) {
                        $converter->setParent($this);
                    }
                });
        }
    }
}
