<?php

namespace App\Provider\Dummy;

use App\Model\Message;
use App\Model\Stop;
use App\Provider\MessageRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class DummyMessageRepository implements MessageRepository
{
    public function getAll(): Collection
    {
        return collect([
            Message::TYPE_INFO,
            Message::TYPE_UNKNOWN,
            Message::TYPE_BREAKDOWN
        ])->map(function ($type) {
            return Message::createFromArray([
                'message'   => 'Lorem ipsum dolor sit amet.',
                'type'      => $type,
                'validFrom' => Carbon::now(),
                'validTo'   => Carbon::now()->addHour()
            ]);
        });
    }

    public function getForStop(Stop $stop): Collection
    {
        return $this->getAll();
    }
}
