<?php

namespace App\Model;

use Tightenco\Collect\Support\Collection;

class Line implements Fillable, Referable
{
    const TYPE_TRAM       = 'tram';
    const TYPE_BUS        = 'bus';
    const TYPE_TRAIN      = 'train';
    const TYPE_METRO      = 'metro';
    const TYPE_TROLLEYBUS = 'trolleybus';
    const TYPE_UNKNOWN    = 'unknown';

    use FillTrait, ReferableTrait;

    /**
     * Line symbol, for example '10', or 'A'
     * @var string
     */
    private $symbol;

    /**
     * Line type tram, bus or whatever.
     * @var string
     */
    private $type;

    /**
     * Is line considered as fast line?
     * @var boolean
     */
    private $fast = false;

    /**
     * Is line considered as night line?
     * @var boolean
     */
    private $night = false;

    /**
     * Line operator
     * @var Operator
     */
    private $operator;

    /**
     * Tracks for this line
     * @var Collection<Track>|Track[]
     */
    private $tracks;


    public function getSymbol(): string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): void
    {
        $this->symbol = $symbol;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): void
    {
        $this->type = $type;
    }

    public function isFast(): bool
    {
        return $this->fast;
    }

    public function setFast(bool $fast): void
    {
        $this->fast = $fast;
    }

    public function isNight(): bool
    {
        return $this->night;
    }

    public function setNight(bool $night): void
    {
        $this->night = $night;
    }

    public function getTracks(): ?Collection
    {
        return $this->tracks;
    }

    public function setTracks($tracks)
    {
        $this->tracks = collect($tracks);
    }

    /**
     * @return Operator
     */
    public function getOperator(): ?Operator
    {
        return $this->operator;
    }

    /**
     * @param Operator $operator
     */
    public function setOperator(Operator $operator): void
    {
        $this->operator = $operator;
    }
}