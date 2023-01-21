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

use App\Dto\Message;
use App\Dto\Stop;
use App\Filter\Requirement\Requirement;
use App\Provider\InMemory\InMemoryRepository;
use App\Provider\MessageRepository;
use App\Service\HandlerProviderFactory;
use App\Service\Proxy\ReferenceFactory;
use Carbon\Carbon;
use Ds\Map;
use Illuminate\Support\Collection;
use Psr\Cache\CacheItemPoolInterface;

class ZtmGdanskMessageRepository extends InMemoryRepository implements MessageRepository
{
    final public const MESSAGES_URL = "http://ckan2.multimediagdansk.pl/displayMessages";

    public function __construct(
        private readonly CacheItemPoolInterface $cache,
        private readonly ZtmGdanskMessageTypeClassifier $classifier,
        private readonly ReferenceFactory $referenceFactory,
        HandlerProviderFactory $handlerProviderFactory
    ) {
        parent::__construct($handlerProviderFactory);
    }

    public function all(Requirement ...$requirements): Collection
    {
        $messagesFromApi = $this->getZtmMessages();
        $messages        = new Map();

        foreach ($messagesFromApi as $messageApiDto) {
            $id = $this->generateIdFromApi($messageApiDto);

            if (!isset($messages[$id])) {
                $messages[$id] = $this->createMessageFromZtm($messageApiDto);
            }

            $message = $messages[$id];

            if (!$message) {
                continue;
            }

            if ($stop = $this->extractStopFromZtm($messageApiDto)) {
                $message->getRefs()->stops->add($stop);
            }
        }

        return $this->filterAndProcessResults(
            result: collect($messages->filter())->values(),
            requirements: $requirements
        );
    }

    private function getZtmMessages(): \Generator
    {
        $item = $this->cache->getItem('ztm-gdansk.messages');

        if (!$item->isHit()) {
            $messages = json_decode(file_get_contents(static::MESSAGES_URL), true, 512, JSON_THROW_ON_ERROR);

            $item->expiresAfter(60);
            $item->set($messages['displaysMsg']);

            $this->cache->save($item);
        }

        yield from $item->get();
    }

    private function generateIdFromApi(array $ztmMessage): string
    {
        $message = $this->extractMessageFromZtm($ztmMessage);

        // theoretically this could result in hash collision
        // but due to similarity of strings this should be negligible
        return md5($message);
    }

    private function createMessageFromZtm(array $ztmMessage): ?Message
    {
        $message = Message::createFromArray([
            'id'        => $this->generateIdFromApi($ztmMessage),
            'message'   => $this->extractMessageFromZtm($ztmMessage),
            'validFrom' => new Carbon($ztmMessage['startDate']),
            'validTo'   => new Carbon($ztmMessage['endDate']),
        ]);

        if ($type = $this->classifier->classify($message)) {
            $message->setType($type);
            return $message;
        }

        return null;
    }

    private function extractStopFromZtm(array $ztmMessage): Stop
    {
        return $this->referenceFactory->get(
            Stop::class,
            $ztmMessage['displayCode'],
        );
    }

    private function extractMessageFromZtm(array $ztmMessage): string
    {
        $message = trim($ztmMessage['messagePart1'] . $ztmMessage['messagePart2']);
        $message = preg_replace('/\s+/', ' ', $message);

        return $message;
    }
}
