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
use App\Model\Line;
use App\Model\Stop;
use App\Model\Track;
use App\Modifier\IdFilter;
use App\Modifier\RelatedFilter;
use App\Provider\TrackRepository;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use function App\Functions\encapsulate;
use function Kadet\Functional\ref;

/**
 * @OA\Tag(name="Tracks")
 */
#[Route(path: '/{provider}/tracks', name: 'track_')]
class TracksController extends Controller
{
    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns all tracks for specific provider, e.g. ZTM Gdańsk.",
     * )
     */
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.0'])]
    public function index(Request $request, TrackRepository $repository)
    {
        $modifiers = $this->getModifiersFromRequest($request);
        return $this->json($repository->all(...$modifiers));
    }

    /**
     *
     * @OA\Tag(name="Tracks")
     *
     * @OA\Response(response=200, description="Stops related to specified query.")
     */
    #[Route(path: '/stops', name: 'stops', methods: ['GET'], options: ['version' => '1.0'])]
    #[Route(path: '/{track}/stops', name: 'stops_in_track', methods: ['GET'], options: ['version' => '1.0'])]
    public function stops(Request $request, TrackRepository $repository)
    {
        $modifiers = $this->getStopsModifiersFromRequest($request);
        return $this->json($repository->stops(...$modifiers));
    }

    private function getModifiersFromRequest(Request $request)
    {
        if ($request->query->has('stop')) {
            $stop = encapsulate($request->query->get('stop'));
            $stop = collect($stop)->map([Stop::class, 'reference']);

            yield new RelatedFilter($stop, Stop::class);
        }

        if ($request->query->has('line')) {
            $line = encapsulate($request->query->get('line'));
            $line = collect($line)->map([Line::class, 'reference']);

            yield new RelatedFilter($line, Line::class);
        }

        if ($request->query->has('id')) {
            $id = encapsulate($request->query->get('id'));

            yield new IdFilter($id);
        }
    }

    private function getStopsModifiersFromRequest(Request $request)
    {
        if ($request->query->has('stop')) {
            $stop = encapsulate($request->query->get('stop'));
            $stop = collect($stop)->map(ref([Stop::class, 'reference']));

            yield new RelatedFilter($stop);
        }

        if ($request->query->has('track') || $request->attributes->has('track')) {
            $track = $request->get('track');
            $track = Track::reference($track);

            yield new RelatedFilter($track);
        }

        if ($request->query->has('id')) {
            $id = encapsulate($request->query->get('id'));

            yield new IdFilter($id);
        }
    }
}
