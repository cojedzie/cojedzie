<?php

namespace App\Service;

use Tightenco\Collect\Support\Collection;
use function Kadet\Functional\Predicates\instance;

class AggregateConverter implements Converter, CacheableConverter
{
    private $converters;

    public function __construct(iterable $converters)
    {
        $this->converters = collect($converters)->each(function (Converter $converter) {
            if ($converter instanceof RecursiveConverter) {
                $converter->setParent($this);
            }
        });
    }

    public function convert($entity)
    {
        /** @var Converter $converter */
        $converter = $this->converters->first(function (Converter $converter) use ($entity) {
            return $converter->supports($entity);
        });

        if ($converter == null) {
            throw new \InvalidArgumentException(sprintf('Cannot convert entity of type %s.', is_object($entity) ? get_class($entity) : gettype($entity)));
        }

        return $converter->convert($entity);
    }

    public function supports($entity)
    {
        return $this->converters->some(function (Converter $converter) use ($entity) {
            return $converter->supports($entity);
        });
    }

    public function getConverters(): Collection
    {
        return clone $this->converters;
    }

    public function flushCache()
    {
        $this
            ->converters
            ->filter(instance(CacheableConverter::class))
            ->each(function (CacheableConverter $converter) {
                $converter->flushCache();
            })
        ;
    }

    public function __clone()
    {
        $this->converters = $this->converters->map(function ($object) {
            return clone $object;
        });
    }
}
