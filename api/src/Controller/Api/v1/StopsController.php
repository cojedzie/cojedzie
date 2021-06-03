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
use App\Model\Stop;
use App\Model\StopGroup;
use App\Model\TrackStop;
use App\Modifier\FieldFilter;
use App\Modifier\IdFilter;
use App\Modifier\RelatedFilter;
use App\Modifier\With;
use App\Provider\StopRepository;
use App\Provider\TrackRepository;
use Illuminate\Support\Collection;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class StopsController
 *
 * @package App\Controller
 * @Route("/{provider}/stops", name="stop_")
 *
 * @OA\Tag(name="Stops")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
class StopsController extends Controller
{
    /**
     * @Route("", methods={"GET"}, name="list", options={"version": "1.0"})
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns all stops for specific provider, e.g. ZTM Gdańsk.",
     *     @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Stop::class)))
     * )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="query",
     *     description="Stop identificators to retrieve at once. Can be used to bulk load data. If not specified will return all data.",
     *     @OA\Schema(type="array", @OA\Items(type="string"))
     * )
     */
    public function index(Request $request, StopRepository $stops)
    {
        $modifiers = $this->getModifiersFromRequest($request);

        return $this->json($stops->all(...$modifiers)->toArray());
    }

    /**
     * @Route("/groups", name="groups", methods={"GET"}, options={"version"="1.0"})
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns grouped stops for specific provider, e.g. ZTM Gdańsk.",
     *     @OA\Schema(type="array", @OA\Items(ref=@Model(type=StopGroup::class)))
     * )
     *
     * @OA\Parameter(
     *     name="name",
     *     in="query",
     *     description="Part of the stop name to search for.",
     *     @OA\Schema(type="string")
     * )
     */
    public function groups(Request $request, StopRepository $stops)
    {
        $modifiers = $this->getModifiersFromRequest($request);

        return $this->json(static::group($stops->all(...$modifiers))->toArray());
    }

    /**
     * @Route("/{stop}", name="details", methods={"GET"}, options={"version"="1.0"})
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns specific stop referenced via identificator.",
     *     @OA\JsonContent(ref=@Model(type=Stop::class))
     * )
     *
     * @OA\Parameter(
     *     name="id",
     *     in="path",
     *     description="Stop identificator as provided by data provider.",
     *     @OA\Schema(type="string")
     * )
     */
    public function one(Request $request, StopRepository $stops, $stop)
    {
        return $this->json($stops->first(new IdFilter($stop), new With("destinations")));
    }

    /**
     * @Route("/{stop}/tracks", name="tracks", methods={"GET"}, options={"version"="1.0"})
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns specific stop referenced via identificator.",
     *     @OA\JsonContent(ref=@Model(type=TrackStop::class))
     * )
     */
    public function tracks(TrackRepository $tracks, $stop)
    {
        return $this->json($tracks->stops(new RelatedFilter(Stop::reference($stop))));
    }

    public static function group(Collection $stops)
    {
        return $stops->groupBy(
            function (Stop $stop) {
                return $stop->getGroup();
            }
        )->map(
            function ($stops, $key) {
                $group = new StopGroup();

                $group->setName($key);
                $group->setStops($stops);

                return $group;
            }
        )->values();
    }

    private function getModifiersFromRequest(Request $request)
    {
        if ($request->query->has('name')) {
            yield FieldFilter::contains('name', $request->query->get('name'));
        }

        if ($request->query->has('id')) {
            yield new IdFilter($request->query->get('id'));
        }

        if ($request->query->has('include-destinations')) {
            yield new With("destinations");
        }
    }
}
