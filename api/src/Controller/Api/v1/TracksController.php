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
use App\Dto\CollectionResult;
use App\Dto\Line;
use App\Dto\Stop;
use App\Dto\Track;
use App\Dto\TrackStop;
use App\Filter\Binding\Http\EmbedParameterBinding;
use App\Filter\Binding\Http\IdConstraintParameterBinding;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Binding\Http\RelatedFilterParameterBinding;
use App\Filter\Requirement\Requirement;
use App\Provider\TrackRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{provider}/tracks', name: 'track_')]
#[OA\Tag(name: 'Tracks')]
#[OA\Parameter(ref: '#/components/parameters/provider')]
class TracksController extends Controller
{
    /**
     * List tracks.
     */
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.1'])]
    #[RelatedFilterParameterBinding(Stop::class, 'stop', relationship: 'stop')]
    #[RelatedFilterParameterBinding(Stop::class, 'destination', relationship: 'destination')]
    #[RelatedFilterParameterBinding(Line::class, 'line')]
    #[EmbedParameterBinding(['stops'])]
    #[IdConstraintParameterBinding]
    #[LimitParameterBinding]
    #[OA\Response(response: 200, description: 'Returns all tracks for specific provider, e.g. ZTM Gdańsk.', content: [new OA\MediaType(mediaType: 'application/vnd.cojedzie.collection+json', schema: new OA\Schema(ref: new Model(type: CollectionResult::class))), new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: Track::class)))])]
    public function index(
        TrackRepository $trackRepository,
        array $requirements
    ): Response {
        $tracks = $trackRepository->all(...$requirements);

        return $this->apiResponseFactory->createCollectionResponse($tracks);
    }

    /**
     * List stops in specific track.
     *
     * @psalm-param iterable<Requirement> $requirements
     */
    #[Route(path: '/{track}/stops', name: 'stops_in_track', methods: ['GET'], options: ['version' => '1.0'])]
    #[ParameterBindingProvider([TrackStopsController::class, 'getParameterBindings'])]
    #[OA\Response(response: 200, description: 'List of track stops matching given criteria.', content: [new OA\MediaType(mediaType: 'application/vnd.cojedzie.collection+json', schema: new OA\Schema(ref: new Model(type: CollectionResult::class))), new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: TrackStop::class)))])]
    public function stops(
        TrackRepository $trackRepository,
        array $requirements
    ): Response {
        $stops = $trackRepository->stops(...$requirements);

        return $this->apiResponseFactory->createCollectionResponse($stops);
    }
}
