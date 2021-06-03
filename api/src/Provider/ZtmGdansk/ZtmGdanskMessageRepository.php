<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Provider\ZtmGdansk;


use App\Model\Message;
use App\Model\Stop;
use App\Provider\MessageRepository;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Symfony\Component\Cache\Adapter\AdapterInterface;

class ZtmGdanskMessageRepository implements MessageRepository
{
    const MESSAGES_URL = "http://ckan2.multimediagdansk.pl/displayMessages";

    private $cache;
    private $classifier;

    /**
     * ZtmGdanskStopRepository constructor.
     */
    public function __construct(AdapterInterface $cache, ZtmGdanskMessageTypeClassifier $classifier)
    {
        $this->cache      = $cache;
        $this->classifier = $classifier;
    }

    public function getAll(): Collection
    {
        return collect($this->queryZtmApi())->unique(function ($message) {
             return $message['messagePart1'] . $message['messagePart2'];
        })->map(function ($message) {
            $message = Message::createFromArray([
                'message'   => trim($message['messagePart1'] . $message['messagePart2']),
                'validFrom' => new Carbon($message['startDate']),
                'validTo'   => new Carbon($message['endDate']),
            ]);

            if ($type = $this->classifier->classify($message)) {
                $message->setType($type);
                return $message;
            }

            return null;
        })->filter()->values();
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
