<?php


namespace App\Provider;


use App\Model\Stop;
use Tightenco\Collect\Support\Collection;

interface StopRepository extends Repository
{
    public function getAll(): Collection;
    public function getAllGroups(): Collection;

    public function getById($id): ?Stop;
    public function getManyById($ids): Collection;

    public function findGroupsByName(string $name): Collection;
}