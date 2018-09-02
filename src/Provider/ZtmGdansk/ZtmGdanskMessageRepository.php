<?php


namespace App\Provider\ZtmGdansk;


use App\Model\Stop;
use App\Provider\MessageRepository;
use Tightenco\Collect\Support\Collection;

class ZtmGdanskMessageRepository implements MessageRepository
{

    public function getAll(): Collection
    {
        return collect();
    }

    public function getForStop(Stop $stop): Collection
    {
        return collect();
    }
}