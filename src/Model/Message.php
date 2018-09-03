<?php


namespace App\Model;


use Carbon\Carbon;

class Message implements Fillable
{
    use FillTrait;

    const TYPE_INFO      = 'info';
    const TYPE_BREAKDOWN = 'breakdown';
    const TYPE_UNKNOWN   = 'unknown';

    /**
     * Message content.
     * @var string
     */
    private $message;

    /**
     * Message type, see TYPE_* constants
     * @var string
     */
    private $type = self::TYPE_UNKNOWN;

    /**
     * Message validity time span start
     * @var Carbon|null
     */
    private $validFrom;

    /**
     * Message validity time span end
     * @var Carbon|null
     */
    private $validTo;

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     */
    public function setMessage(string $message): void
    {
        $this->message = $message;
    }

    /**
     * @return mixed
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param mixed $type
     */
    public function setType($type): void
    {
        $this->type = $type;
    }

    /**
     * @return Carbon|null
     */
    public function getValidFrom(): ?Carbon
    {
        return $this->validFrom;
    }

    /**
     * @param Carbon|null $validFrom
     */
    public function setValidFrom(?Carbon $validFrom): void
    {
        $this->validFrom = $validFrom;
    }

    /**
     * @return Carbon|null
     */
    public function getValidTo(): ?Carbon
    {
        return $this->validTo;
    }

    /**
     * @param Carbon|null $validTo
     */
    public function setValidTo(?Carbon $validTo): void
    {
        $this->validTo = $validTo;
    }
}