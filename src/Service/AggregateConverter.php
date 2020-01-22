<?php

namespace App\Service;

use Hoa\Iterator\Recursive\Recursive;
use Symfony\Component\DependencyInjection\ServiceLocator;
use function Kadet\Functional\Predicates\equals;
use function Kadet\Functional\Predicates\method;

class AggregateConverter implements Converter
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
}
