<?php


namespace App\Provider;


use App\Modifier\Modifier;

interface DepartureRepository extends Repository
{
    public function current(iterable $stops, Modifier ...$modifiers);
}
