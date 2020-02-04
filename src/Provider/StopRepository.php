<?php


namespace App\Provider;


use App\Model\Stop;
use Tightenco\Collect\Support\Collection;

interface StopRepository extends Repository
{
    public function getAll(): Collection;
    public function getById($id): ?Stop;
    public function getManyById($ids): Collection;
    public function findByName(string $name): Collection;
}
