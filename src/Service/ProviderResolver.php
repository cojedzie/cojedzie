<?php


namespace App\Service;

use App\Exception\NonExistentServiceException;
use App\Provider\Dummy\DummyProvider;
use App\Provider\Provider;
use Kadet\Functional\Transforms as t;
use Kadet\Functional\Predicates as p;
use Tightenco\Collect\Support\Collection;

class ProviderResolver
{
    private $providers;

    public function __construct($providers, bool $debug)
    {
        $this->providers = collect($providers)->keyBy(t\property('identifier'));

        if (!$debug) {
            $this->providers = $this->providers->filter(p\instance(DummyProvider::class)->negate());
        }
    }

    /**\
     * @param string $name
     *
     * @return \App\Provider\Provider
     * @throws \App\Exception\NonExistentServiceException
     */
    public function resolve(string $name): Provider
    {
        if (!$this->providers->has($name)) {
            $message = sprintf("Provider '%s' doesn't exist, you can choose from: %s", $name, $this->providers->keys()->implode(', '));
            throw new NonExistentServiceException($message);
        }

        return $this->providers->get($name);
    }

    /** @return Provider[] */
    public function all(): Collection
    {
        return clone $this->providers;
    }
}