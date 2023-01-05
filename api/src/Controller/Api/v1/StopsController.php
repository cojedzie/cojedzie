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

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Filter\Binding\Http\EmbedParameterBinding;
use App\Filter\Binding\Http\FieldFilterParameterBinding;
use App\Filter\Binding\Http\IdConstraintParameterBinding;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Binding\Http\ParameterBinding;
use App\Filter\Binding\Http\ParameterBindingGroup;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Binding\Http\RelatedFilterParameterBinding;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Filter\Requirement\IdConstraint;
use App\Filter\Requirement\RelatedFilter;
use App\Filter\Requirement\Requirement;
use App\Dto\Stop;
use App\Dto\StopGroup;
use App\Dto\TrackStop;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use Illuminate\Support\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StopsController
 *
 * @package App\Controller
 *
 * @OA\Tag(name="Stops")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
#[Route(path: '/{provider}/stops', name: 'stop_')]
class StopsController extends Controller
{
    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns all stops for specific provider, e.g. ZTM Gdańsk.",
     *     @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Stop::class)))
     * )
     *
     * @psalm-param iterable<Requirement> $requirements
     */
    #[Route(path: '', methods: ['GET'], name: 'list', options: ['version' => '1.0'])]
    #[ParameterBindingProvider([__CLASS__, 'getParameterBinding'])]
    public function index(
        StopRepository $stopRepository,
        array $requirements
    ): Response {
        $stops = $stopRepository->all(...$requirements);

        return $this->json($stops);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns grouped stops for specific provider, e.g. ZTM Gdańsk.",
     *     @OA\Schema(type="array", @OA\Items(ref=@Model(type=StopGroup::class)))
     * )
     *
     * @psalm-param iterable<Requirement> $requirements
     */
    #[Route(path: '/groups', name: 'groups', methods: ['GET'], options: ['version' => '1.0'])]
    #[ParameterBindingProvider([__CLASS__, 'getParameterBinding'])]
    public function groups(
        StopRepository $stopRepository,
        iterable $requirements
    ): Response {
        $groups = static::group($stopRepository->all(...$requirements))->toArray();

        return $this->json($groups);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns specific stop referenced via identificator.",
     *     @OA\JsonContent(ref=@Model(type=Stop::class))
     * )
     */
    #[Route(path: '/{stop}', name: 'details', methods: ['GET'], options: ['version' => '1.0'])]
    public function one(
        StopRepository $stopRepository,
        #[IdConstraintParameterBinding(parameter: 'stop', from: ['attributes'])]
        IdConstraint $stop
    ): Response {
        return $this->json(
            $stopRepository->first(
                $stop,
                new Embed("destinations")
            )
        );
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns specific stop referenced via identificator.",
     *     @OA\JsonContent(ref=@Model(type=TrackStop::class))
     * )
     */
    #[Route(path: '/{stop}/tracks', name: 'tracks', methods: ['GET'], options: ['version' => '1.0'])]
    public function tracks(
        TrackRepository $trackRepository,
        #[RelatedFilterParameterBinding(parameter: 'stop', resource: Stop::class, from: ['attributes'])]
        RelatedFilter $stop
    ): Response {
        $stops = $trackRepository->stops($stop);

        return $this->json($stops);
    }

    public static function group(Collection $stops)
    {
        return $stops->groupBy(
            fn (Stop $stop) => $stop->getGroup()
        )->map(
            function ($stops, $key) {
                $group = new StopGroup();

                $group->setName($key);
                $group->setStops($stops);

                return $group;
            }
        )->values();
    }

    /**
     * @psalm-return ParameterBinding[]
     */
    public static function getParameterBinding(): ParameterBinding
    {
        return new ParameterBindingGroup(
            new IdConstraintParameterBinding(
                documentation: [
                    'description' => 'Stop unique identifier as provided by data provider.',
                ]
            ),
            new LimitParameterBinding(),
            new EmbedParameterBinding(['destinations']),
            new FieldFilterParameterBinding(
                parameter: 'name',
                field: 'name',
                defaultOperator: FieldFilterOperator::Contains,
                operators: FieldFilterParameterBinding::STRING_OPERATORS,
                options: [
                    FieldFilter::OPTION_CASE_SENSITIVE => false,
                ],
                documentation: [
                    'description' => 'Part of the stop name to search for.',
                    'schema'      => [
                        'type' => 'string',
                    ],
                ]
            ),
        );
    }
}
