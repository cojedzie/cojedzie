<?php


namespace App\Model;


class Line implements Fillable, Referable
{
    use FillTrait, ReferenceTrait;

    const TYPE_TRAM       = 'tram';
    const TYPE_BUS        = 'bus';
    const TYPE_TRAIN      = 'train';
    const TYPE_METRO      = 'metro';
    const TYPE_TROLLEYBUS = 'trolleybus';
    const TYPE_UNKNOWN    = 'unknown';

    /**
     * Some kind of identification for provider
     * @var mixed
     */
    private $id;

    /**
     * Line symbol, for example '10', or 'A'
     * @var string
     */
    private $symbol;

    /**
     * Line variant, for example 'a'
     * @var string|null
     */
    private $variant;

    /**
     * Line type tram, bus or whatever.
     * @var string
     */
    private $type;

    public function getId()
    {
        return $this->id;
    }

    public function setId($id): void
    {
        $this->id = $id;
    }

    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getVariant(): ?string
    {
        return $this->variant;
    }

    public function setVariant(?string $variant): void
    {
        $this->variant = $variant;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }
}