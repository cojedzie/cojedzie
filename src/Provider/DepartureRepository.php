<?php


namespace App\Provider;


use App\Model\Stop;
use App\Modifier\Modifier;
use Tightenco\Collect\Support\Collection;

interface DepartureRepository extends Repository
{
    public function current(iterable $stops, Modifier ...$modifiers);
}
