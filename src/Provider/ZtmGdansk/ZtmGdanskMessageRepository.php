<?php


namespace App\Provider\ZtmGdansk;


use App\Model\Message;
use App\Model\Stop;
use App\Provider\MessageRepository;
use Carbon\Carbon;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Tightenco\Collect\Support\Collection;

class ZtmGdanskMessageRepository implements MessageRepository
{
    const MESSAGES_URL = "http://87.98.237.99:88/displayMessages";

    private $cache;

    /**
     * ZtmGdanskStopRepository constructor.
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getAll(): Collection
    {
        return collect($this->queryZtmApi())->unique(function ($message) {
             return $message['messagePart1'] . $message['messagePart2'];
        })->map(function ($message) {
            return Message::createFromArray([
                'message'   => trim($message['messagePart1'] . $message['messagePart2']),
                'type'      => Message::TYPE_UNKNOWN,
                'validFrom' => new Carbon($message['startDate']),
                'validTo'   => new Carbon($message['endDate']),
            ]);
        });
    }

    public function getForStop(Stop $stop): Collection
    {
        return $this->getAll();
    }

    private function queryZtmApi()
    {
        $item = $this->cache->getItem('ztm-gdansk.messages');

        if (!$item->isHit()) {
            $messages = json_decode(file_get_contents(static::MESSAGES_URL), true);

            $item->expiresAfter(60);
            $item->set($messages['displaysMsg']);

            $this->cache->save($item);
        }

        return $item->get();
    }
}