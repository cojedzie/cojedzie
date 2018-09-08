<?php

namespace App\Provider\Database;

use App\Model\Operator;
use App\Provider\OperatorRepository;
use Tightenco\Collect\Support\Collection;

class GenericOperatorRepository extends DatabaseRepository implements OperatorRepository
{
    public function getAll(): Collection
    {
        $repository = $this->em->getRepository(Operator::class);
        $operators = $repository->findAll();

        return collect($operators);
    }

    public function getById($id): ?Operator
    {
        $repository = $this->em->getRepository(Operator::class);

        return $repository->find($id);
    }

    public function getManyById($ids): Collection
    {
        $repository = $this->em->getRepository(Operator::class);
        $operators      = $repository->findBy(['id' => $ids]);

        return collect($operators);
    }
}