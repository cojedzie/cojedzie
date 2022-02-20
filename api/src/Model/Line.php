<?php
/*
 * Copyright (C) 2021 Kacper Donat
 *
 * @author Kacper Donat <kacper@kadet.net>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Model;

use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;

class Line implements Fillable, Referable, DTO
{
    final const TYPE_TRAM       = 'tram';
    final const TYPE_BUS        = 'bus';
    final const TYPE_TRAIN      = 'train';
    final const TYPE_METRO      = 'metro';
    final const TYPE_TROLLEYBUS = 'trolleybus';
    final const TYPE_UNKNOWN    = 'unknown';

    use FillTrait, ReferableTrait;

    /**
     * Line symbol, for example '10', or 'A'
     * @Serializer\Type("string")
     * @OA\Property(example="10")
     */
    private string $symbol;

    /**
     * Line type tram, bus or whatever.
     * @Serializer\Type("string")
     * @OA\Property(type="string", enum={
     *     Line::TYPE_BUS,
     *     Line::TYPE_UNKNOWN,
     *     Line::TYPE_METRO,
     *     Line::TYPE_TRAIN,
     *     Line::TYPE_TRAM,
     *     Line::TYPE_TROLLEYBUS
     * })
     */
    private string $type;

    /**
     * Is line considered as fast line?
     * @Serializer\Type("bool")
     */
    private bool $fast = false;

    /**
     * Is line considered as night line?
     * @Serializer\Type("bool")
     */
    private bool $night = false;

    /**
     * Line operator
     * @Serializer\Type(Operator::class)
     * @OA\Property(ref=@Model(type=Operator::class, groups={"Identity"}))
     */
    private Operator $operator;

    /**
     * Tracks for this line
     * @Serializer\Type("Collection")
     * @OA\Property(type="array", @OA\Items(ref=@Model(type=Track::class)))
     * @Serializer\Groups({"Full"})
     * @var Collection<Track>
     */
    private Collection $tracks;


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

    public function setOperator(Operator $operator): void
    {
        $this->operator = $operator;
    }
}
