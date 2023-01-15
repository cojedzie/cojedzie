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

namespace App\Dto;

use App\Serialization\SerializeAs;
use Illuminate\Support\Collection;
use JMS\Serializer\Annotation as Serializer;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Serializer\Annotation\Context;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[ContentType('vnd.cojedzie.line')]
class Line implements Fillable, Referable, Dto
{
    final public const TYPE_TRAM       = 'tram';
    final public const TYPE_BUS        = 'bus';
    final public const TYPE_TRAIN      = 'train';
    final public const TYPE_METRO      = 'metro';
    final public const TYPE_TROLLEYBUS = 'trolleybus';
    final public const TYPE_UNKNOWN    = 'unknown';

    use FillTrait, ReferableTrait;

    /**
     * Line symbol, for example '10', or 'A'
     * @OA\Property(example="10")
     */
    private string $symbol;

    /**
     * Line type tram, bus or whatever.
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
     */
    private bool $fast = false;

    /**
     * Is line considered as night line?
     */
    private bool $night = false;

    /**
     * Line operator
     * @OA\Property(ref=@Model(type=Operator::class, groups={"Reference"}))
     */
    #[SerializeAs(['Default' => 'Reference'])]
    #[Context(context: [AbstractNormalizer::GROUPS => ['reference']])]
    private Operator $operator;

    /**
     * Tracks for this line
     * @OA\Property(type="array", @OA\Items(ref=@Model(type=Track::class)))
     * @var Collection<Track>
     */
    #[Serializer\Groups(['Full'])]
    #[Groups(['full'])]
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
