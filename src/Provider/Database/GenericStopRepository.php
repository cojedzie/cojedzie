<?php

namespace App\Provider\Database;

use App\Entity\StopEntity;
use App\Model\Stop;
use App\Model\StopGroup;
use App\Provider\StopRepository;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;

class GenericStopRepository extends DatabaseRepository implements StopRepository
{
    public function getAll(): Collection
    {
        $stops = $this->em->getRepository(StopEntity::class)->findAll();

        return collect($stops)->map(\Closure::fromCallable([$this, 'convert']));
    }

    public function getAllGroups(): Collection
    {
        return $this->group($this->getAll());
    }

    public function getById($id): ?Stop
    {
        $id = $this->id->generate($this->provider, $id);
        $stop = $this->em->getRepository(StopEntity::class)->find($id);

        return $this->convert($stop);
    }

    public function getManyById($ids): Collection
    {
        $ids = collect($ids)->map(f\apply(f\ref([$this->id, 'generate']), $this->provider));

        $stops = $this->em->getRepository(StopEntity::class)->findBy(['id' => $ids->all()]);
        return collect($stops)->map(\Closure::fromCallable([$this, 'convert']));
    }

    public function findGroupsByName(string $name): Collection
    {
        $query = $this->em->createQueryBuilder()
            ->select('s')
            ->from(StopEntity::class, 's')
            ->where('s.name LIKE :name')
            ->getQuery();

        $stops = collect($query->execute([':name' => "%$name%"]))->map(\Closure::fromCallable([$this, 'convert']));

        return $this->group($stops);
    }

    private function convert(StopEntity $entity): Stop
    {
        return Stop::createFromArray([
            'id'          => $this->id->of($entity),
            'name'        => $entity->getName(),
            'description' => $entity->getDescription(),
            'variant'     => $entity->getVariant(),
            'onDemand'    => $entity->isOnDemand(),
            'location'    => [
                $entity->getLatitude(),
                $entity->getLongitude(),
            ],
        ]);
    }

    private function group(Collection $stops)
    {
        return $stops->groupBy(function (Stop $stop) {
            return $stop->getName();
        })->map(function ($group, $key) {
            $group = new StopGroup($group);
            $group->setName($key);

            return $group;
        });
    }
}