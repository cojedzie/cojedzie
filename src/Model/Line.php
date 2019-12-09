<?php

namespace App\Model;

use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
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
     * @Serializer\Type("string")
     * @SWG\Property(example="10")
     * @var string
     */
    private $symbol;

    /**
     * Line type tram, bus or whatever.
     * @Serializer\Type("string")
     * @SWG\Property(type="string", enum={
     *     Line::TYPE_BUS,
     *     Line::TYPE_UNKNOWN,
     *     Line::TYPE_METRO,
     *     Line::TYPE_TRAIN,
     *     Line::TYPE_TRAM,
     *     Line::TYPE_TROLLEYBUS
     * })
     * @var string
     */
    private $type;

    /**
     * Is line considered as fast line?
     * @Serializer\Type("bool")
     * @var boolean
     */
    private $fast = false;

    /**
     * Is line considered as night line?
     * @Serializer\Type("bool")
     * @var boolean
     */
    private $night = false;

    /**
     * Line operator
     * @Serializer\Type(Operator::class)
     * @SWG\Property(ref=@Model(type=Operator::class, groups={"Identity"}))
     * @var Operator
     */
    private $operator;

    /**
     * Tracks for this line
     * @Serializer\Type("Collection")
     * @SWG\Property(type="array", @SWG\Items(ref=@Model(type=Track::class)))
     * @Serializer\Groups("Full")
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