<?php

namespace App\Provider;

use App\Model\Track;
use Tightenco\Collect\Support\Collection;

interface TrackRepository
{
    public function getAll(): Collection;

    public function getById($id): Track;
    public function getManyById($ids): Collection;

    public function getByStop($stop): Collection;
    public function getByLine($line): Collection;
}