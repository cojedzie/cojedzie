<?php

namespace App\Provider\Dummy;

use App\Model\Stop;
use App\Modifier\Modifier;
use App\Provider\StopRepository;
use App\Service\Proxy\ReferenceFactory;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;

class DummyStopRepository implements StopRepository
{
    private $reference;

    /**
     * DummyDepartureProviderRepository constructor.
     *
     * @param $reference
     */
    public function __construct(ReferenceFactory $reference)
    {
        $this->reference = $reference;
    }

    public function getAll(): Collection
    {
        return collect();
    }

    public function getById($id): ?Stop
    {
        return Stop::createFromArray(['id' => $id, 'name' => 'lorem']);
    }

    public function getManyById($ids): Collection
    {
        return collect($ids)->map(f\ref([ $this, 'getById' ]));
    }

    public function findByName(string $name): Collection
    {
        return collect();
    }

    public function first(Modifier ...$modifiers)
    {
        // TODO: Implement first() method.
    }

    public function all(Modifier ...$modifiers): Collection
    {
        // TODO: Implement all() method.
    }
}
