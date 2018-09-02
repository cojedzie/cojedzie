<?php

namespace App\Provider\ZtmGdansk;

use App\Model\Stop;
use App\Model\StopGroup;
use App\Provider\StopRepository;
use Symfony\Component\Cache\Adapter\AdapterInterface;
use Tightenco\Collect\Support\Collection;

class ZtmGdanskStopRepository implements StopRepository
{
    const STOPS_URL = 'http://91.244.248.19/dataset/c24aa637-3619-4dc2-a171-a23eec8f2172/resource/cd4c08b5-460e-40db-b920-ab9fc93c1a92/download/stops.json';

    private $cache;

    /**
     * ZtmGdanskStopRepository constructor.
     */
    public function __construct(AdapterInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getAllGroups(): Collection
    {
        $stops = $this->getAllStops();
        return $this->group($stops);
    }

    public function findGroupsByName(string $name): Collection
    {
        if (empty($name)) {
            return collect();
        }

        $stops = $this->getAllStops();
        $stops = $stops->filter(function (Stop $stop) use ($name) {
             return stripos($stop->getName(), $name) !== false;
        });

        return $this->group($stops);
    }

    public function getAllStops(): Collection
    {
        static $stops = null;
        if ($stops === null) {
            $stops = collect($this->queryZtmApi())->map(function ($stop) {
                return Stop::createFromArray([
                    'id'       => $stop['stopId'],
                    'name'     => trim($stop['stopName'] ?? $stop['stopDesc']),
                    'variant'  => trim($stop['zoneName'] == 'Gdańsk' ? $stop['subName'] : null),
                    'location' => [$stop['stopLat'], $stop['stopLon']],
                    'onDemand' => (bool)$stop['onDemand'],
                ]);
            })->keyBy(function (Stop $stop) {
                return $stop->getId();
            })->sort(function (Stop $a, Stop $b) {
                return (int)$a->getVariant() <=> (int)$b->getVariant();
            });
        }

        return $stops;
    }

    public function getById($id): ?Stop
    {
        return $this->getAllStops()->get($id);
    }

    public function getManyById($ids): Collection
    {
        $stops = $this->getAllStops();

        return collect($ids)->mapWithKeys(function ($id) use ($stops) {
            return [$id => $stops[$id]];
        });
    }

    private function queryZtmApi()
    {
        $item = $this->cache->getItem('ztm-gdansk.stops');

        if (!$item->isHit()) {
            $stops = json_decode(file_get_contents(static::STOPS_URL), true);

            $item->expiresAfter(24 * 60 * 60);
            $item->set($stops[date('Y-m-d')]['stops']);

            $this->cache->save($item);
        }

        return $item->get();
    }

    private function group(Collection $stops)
    {
        return $stops->groupBy(function (Stop $stop) {
            return $stop->getName();
        })->map(function ($group, $key) {
            $group = new StopGroup($group);
            $group->setName($key);

            return $group;
        });
    }
}