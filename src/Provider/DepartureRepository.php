<?php


namespace App\Provider;


use App\Model\Stop;
use Tightenco\Collect\Support\Collection;

interface DepartureRepository extends Repository
{
    public function getForStop(Stop $stop): Collection;
}