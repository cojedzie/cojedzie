<?php


namespace App\Provider;


use App\Model\Stop;
use Tightenco\Collect\Support\Collection;

interface MessageRepository
{
    public function getAll(): Collection;
    public function getForStop(Stop $stop): Collection;
}