<?php


namespace App\Model;


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
    public $message;

    /**
     * Message type, see TYPE_* constants
     * @var
     */
    public $type = self::TYPE_UNKNOWN;

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
}