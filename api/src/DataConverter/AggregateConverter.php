<?php

namespace App\DataConverter;

use Illuminate\Support\Collection;
use function collect;
use function Kadet\Functional\Predicates\instance;

class AggregateConverter implements Converter, CacheableConverter
{
    private $converters;
    private $cachedConverters;

    public function __construct(iterable $converters)
    {
        $this->converters = $converters;
    }

    public function convert($entity, string $type)
    {
        $this->ensureCachedConverters();

        /** @var Converter $converter */
        $converter = $this->cachedConverters->first(fn (Converter $converter) => $converter->supports($entity, $type));

        if ($converter == null) {
            throw new \InvalidArgumentException(sprintf('Cannot convert entity of type %s.', is_object($entity) ? get_class($entity) : gettype($entity)));
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

        $this->cachedConverters = $this->cachedConverters->map(function ($object) {
            return clone $object;
        });
    }

    private function ensureCachedConverters()
    {
        if (!$this->cachedConverters) {
            $this->cachedConverters = collect($this->converters)
                ->filter(function (Converter $converter) {
                    return $converter !== $this && !$converter instanceof AggregateConverter;
                })
                ->each(function (Converter $converter) {
                    if ($converter instanceof RecursiveConverter) {
                        $converter->setParent($this);
                    }
                });
        }
    }
}
