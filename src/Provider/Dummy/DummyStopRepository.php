<?php

namespace App\Provider\Dummy;

use App\Model\Stop;
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

    public function getAllGroups(): Collection
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

    public function findGroupsByName(string $name): Collection
    {
        return collect();
    }
}