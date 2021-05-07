<?php

namespace App\Model\Status;

use App\Model\DTO;
use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;
use function App\Functions\setup;

class Time implements DTO
{
    /**
     * Current date and time on node.
     *
     * @Serializer\Type("Carbon")
     * @OA\Property(type="string", format="date-time")
     */
    private Carbon $current;

    /**
     * Timezone for this node.
     *
     * @Serializer\Type("string")
     * @OA\Property(type="string", format="timezone", example="Europe/Warsaw")
     */
    private string $timezone;

    public static function createFromDateTime(Carbon $now)
    {
        return setup(new static, function (Time $time) use ($now) {
            $time->setCurrent($now);
            $time->setTimezone($now->timezoneName);
        });
    }

    public function getCurrent(): Carbon
    {
        return $this->current;
    }

    public function setCurrent(Carbon $current): void
    {
        $this->current = $current;
    }

    public function getTimezone(): string
    {
        return $this->timezone;
    }

    public function setTimezone(string $timezone): void
    {
        $this->timezone = $timezone;
    }
}
