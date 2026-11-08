<?php
/*
 * Copyright (C) 2026 Kacper Donat
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

declare(strict_types=1);

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Dto\CollectionResult;
use App\Dto\Departure;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Binding\Http\ParameterBinding;
use App\Filter\Binding\Http\ParameterBindingGroup;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Binding\Http\TimetableScopeParameterBinding;
use App\Filter\Binding\Http\TimetableTimeParameterBinding;
use App\Filter\Requirement\Requirement;
use App\Provider\TimetableRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[OA\Tag(name: 'Timetable')]
#[OA\Parameter(ref: '#/components/parameters/provider')]
class TimetableController extends Controller
{
    /**
     * List the planned departures of stops, lines, tracks and trips, for a day or a range of time.
     *
     * @param list<Requirement> $requirements
     */
    #[Route(path: '/{provider}/timetable', name: 'timetable_list', methods: ['GET'], options: ['version' => '1.0'])]
    #[Route(path: '/{provider}/stops/{stop}/timetable', name: 'stop_timetable', methods: ['GET'], options: ['version' => '1.0'])]
    #[Route(path: '/{provider}/lines/{line}/timetable', name: 'line_timetable', methods: ['GET'], options: ['version' => '1.0'])]
    #[ParameterBindingProvider([self::class, 'getParameterBinding'])]
    #[OA\Response(
        response: 200,
        description: 'Planned departures ordered by departure time, then by line.',
        content: [
            new OA\MediaType(
                mediaType: 'application/vnd.cojedzie.collection+json',
                schema: new OA\Schema(
                    ref: new Model(type: CollectionResult::class),
                ),
            ),
            new OA\JsonContent(
                type: 'array',
                items: new OA\Items(
                    ref: new Model(
                        type: Departure::class,
                        groups: ['default', Departure::TIMETABLE_GROUP],
                    ),
                ),
            ),
        ],
    )]
    public function list(TimetableRepository $timetable, array $requirements): Response
    {
        return $this->apiResponseFactory->createCollectionResponse(
            $timetable->departures(...$requirements),
            context: [
                AbstractNormalizer::GROUPS => [Departure::TIMETABLE_GROUP],
            ],
        );
    }

    public static function getParameterBinding(): ParameterBinding
    {
        return new ParameterBindingGroup(
            new TimetableScopeParameterBinding(),
            new TimetableTimeParameterBinding(),
            // a page holds a stop's day, the offset pages through a line's
            new LimitParameterBinding(defaultLimit: 1000, maxLimit: 1000),
        );
    }
}
