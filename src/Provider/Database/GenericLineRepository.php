<?php

namespace App\Provider\Database;

use App\Entity\LineEntity;
use App\Model\Line;
use App\Provider\LineRepository;
use Tightenco\Collect\Support\Collection;
use Kadet\Functional as f;

class GenericLineRepository extends DatabaseRepository implements LineRepository
{
    public function getAll(): Collection
    {
        $repository = $this->em->getRepository(LineEntity::class);
        $lines      = $repository->findAll();

        return collect($lines)->map(f\ref([$this, 'convert']));
    }

    public function getById($id): ?Line
    {
        $repository = $this->em->getRepository(LineEntity::class);
        return $this->convert($repository->find($id));
    }

    public function getManyById($ids): Collection
    {
        $ids = collect($ids)->map(f\apply(f\ref([$this->id, 'generate']), $this->provider));

        $repository = $this->em->getRepository(LineEntity::class);
        $lines      = $repository->findBy(['id' => $ids->all()]);

        return collect($lines)->map(f\ref([$this, 'convert']));
    }

    private function convert(LineEntity $line): Line
    {
        return Line::createFromArray([
            'id'       => $this->id->of($line),
            'symbol'   => $line->getSymbol(),
            'night'    => $line->isNight(),
            'fast'     => $line->isFast(),
            'type'     => $line->getType()
        ]);
    }
}