<?php

namespace App\Provider\ZtmGdansk;

use App\Model\Message;

class ZtmGdanskMessageTypeClassifier
{
    public function classify(Message $message): string
    {
        switch (true) {
            case preg_match('/(awari|opóźnie)/i', $message->getMessage()):
                return Message::TYPE_BREAKDOWN;

            case preg_match('#gdansk.pl/powietrze#i', $message->getMessage()):
                return Message::TYPE_INFO;

            default:
                return Message::TYPE_UNKNOWN;
        }
    }
}