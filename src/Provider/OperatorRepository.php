<?php


namespace App\Provider;


use App\Model\Operator;
use Tightenco\Collect\Support\Collection;

interface OperatorRepository
{
    public function getAll(): Collection;
    public function getById($id): ?Operator;
    public function getManyById($ids): Collection;
}