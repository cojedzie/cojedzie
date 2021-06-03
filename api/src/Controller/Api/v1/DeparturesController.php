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
use App\Model\Departure;
use App\Modifier\IdFilter;
use App\Modifier\Limit;
use App\Provider\DepartureRepository;
use App\Provider\StopRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DeparturesController
 *
 * @Route("/{provider}/departures", name="departure_")
 *
 * @OA\Tag(name="Departures")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
class DeparturesController extends Controller
{
    /**
     * @Route("/{stop}", name="stop", methods={"GET"}, options={"version": "1.0"})
     * @OA\Response(
     *     description="Gets departures from particular stop.",
     *     response=200,
     *     @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Departure::class)))
     * )
     */
    public function stop(DepartureRepository $departures, StopRepository $stops, $stop, Request $request)
    {
        $stop = $stops->first(new IdFilter($stop));

        return $this->json($departures->current(collect($stop), ...$this->getModifiersFromRequest($request)));
    }

    /**
     * @Route("", name="list", methods={"GET"}, options={"version": "1.0"})
     *
     * @OA\Response(
     *     description="Gets departures from given stops.",
     *     response=200,
     *     @OA\Schema(type="array", @OA\Items(ref=@Model(type=Departure::class)))
     * )
     *
     * @OA\Parameter(
     *     name="stop",
     *     description="Stop identifiers.",
     *     in="query",
     *     @OA\Schema(type="array", @OA\Items(type="string")),
     * )
     *
     * @OA\Parameter(
     *     name="limit",
     *     description="Max departures count.",
     *     @OA\Schema(type="integer"),
     *     in="query"
     * )
     */
    public function stops(DepartureRepository $departures, StopRepository $stops, Request $request)
    {
        $stops  = $stops->all(new IdFilter($request->query->get('stop', [])));
        $result = $departures->current($stops, ...$this->getModifiersFromRequest($request));

        return $this->json(
            $result->values()->slice(0, (int)$request->query->get('limit', 8)),
            200,
            [],
            $this->serializerContextFactory->create(Departure::class, ['Default'])
        );
    }

    private function getModifiersFromRequest(Request $request)
    {
        if ($request->query->has('limit')) {
            yield Limit::count($request->query->getInt('limit'));
        }
    }
}
