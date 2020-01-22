<?php

namespace App\Provider;

use App\Model\Trip;

interface TripRepository
{
    public function getById(string $id): Trip;
}
