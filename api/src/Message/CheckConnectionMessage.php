<?php

namespace App\Message;

use Symfony\Component\Uid\Uuid;

final class CheckConnectionMessage
{
    private Uuid $connectionId;

    public function __construct(Uuid $connectionId)
    {
        $this->connectionId = $connectionId;
    }

    public function getConnectionId(): Uuid
    {
        return $this->connectionId;
    }
}
