<?php

namespace App\Provider\ZtmGdansk;

use App\Dto\Message;
use Ds\Collection;
use Ds\Set;

class ZtmGdanskMessageLineExtractor
{
    public function extractLinesFromMessage(Message $message): Collection
    {
        if (preg_match_all('/lini(?:i?|ach)\s*(?:nr\.?)?:?\s+((?:(?-i:[A-Z]{1,2}|\d+|[A-Z]{1,2}\d+)\b\s*(?:[,;]|i|oraz)?\s*)*)\.?/imu', $message->getMessage(), $matches) === 0) {
            return new Set();
        }

        $set = new Set();

        foreach ($matches[1] as $match) {
            $lines = preg_split('/\s*([,;]|i|oraz)\s*/i', trim($match));
            $set->add(...$lines);
        }

        return $set;
    }
}
