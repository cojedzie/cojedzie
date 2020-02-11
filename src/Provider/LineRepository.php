<?php


namespace App\Provider;


use App\Model\Line;
use Tightenco\Collect\Support\Collection;

interface LineRepository extends FluentRepository
{
    public function getAll(): Collection;

    public function getById($id): ?Line;
    public function getManyById($ids): Collection;
}
