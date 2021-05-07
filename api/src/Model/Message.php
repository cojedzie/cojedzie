<?php

namespace App\Model;

use Carbon\Carbon;
use JMS\Serializer\Annotation as Serializer;
use OpenApi\Annotations as OA;

class Message implements Fillable, DTO
{
    use FillTrait;

    const TYPE_INFO      = 'info';
    const TYPE_BREAKDOWN = 'breakdown';
    const TYPE_UNKNOWN   = 'unknown';

    /**
     * Message content.
     * @Serializer\Type("string")
     * @OA\Property(example="Tram accident on Haller alley, possible delays on lines: 2, 3, 4, 5.")
     * @var string
     */
    private $message;

    /**
     * Message type, see TYPE_* constants
     * @Serializer\Type("string")
     * @OA\Property(type="string", enum={ Message::TYPE_INFO, Message::TYPE_BREAKDOWN, Message::TYPE_UNKNOWN })
     * @var string
     */
    private $type = self::TYPE_UNKNOWN;

    /**
     * Message validity time span start
     * @Serializer\Type("Carbon")
     * @OA\Property(type="string", format="date-time")
     * @var Carbon|null
     */
    private $validFrom;

    /**
     * Message validity time span end
     * @var Carbon|null
     * @Serializer\Type("Carbon")
     * @OA\Property(type="string", format="date-time")
     */
    private $validTo;

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type): void
    {
        $this->type = $type;
    }

    public function getValidFrom(): ?Carbon
    {
        return $this->validFrom;
    }

    public function setValidFrom(?Carbon $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    public function getValidTo(): ?Carbon
    {
        return $this->validTo;
    }

    public function setValidTo(?Carbon $validTo): void
    {
        $this->validTo = $validTo;
    }
}
