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
use App\Dto\Departure;
use App\Filter\Binding\Http\IdConstraintParameterBinding;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Requirement\IdConstraint;
use App\Provider\DepartureRepository;
use App\Provider\StopRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @OA\Tag(name="Departures")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
#[Route(path: '/{provider}/departures', name: 'departure_')]
class DeparturesController extends Controller
{
    /**
     * List departures.
     *
     * @OA\Response(
     *     description="List of departures valid at time of request",
     *     response=200,
     *     @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Departure::class)))
     * )
     */
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.1'])]
    #[LimitParameterBinding]
    public function stops(
        DepartureRepository $departureRepository,
        StopRepository $stopRepository,
        #[IdConstraintParameterBinding(
            parameter: 'stop',
            documentation: [
                'description' => 'Stop identifiers as provided by data provider.',
            ]
        )]
        ?IdConstraint $stops,
        array $requirements
    ): Response {
        $stops      = $stopRepository->all($stops);
        $departures = $departureRepository->current($stops, ...$requirements);

        return $this->apiResponseFactory->createCollectionResponse($departures);
    }
}
