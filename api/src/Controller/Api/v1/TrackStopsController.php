<?php
/*
 * Copyright (C) 2022 Kacper Donat
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
use App\Filter\Binding\Http\FieldFilterParameterBinding;
use App\Filter\Binding\Http\ImportFilterParameterBinding;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Binding\Http\ParameterBinding;
use App\Filter\Binding\Http\ParameterBindingGroup;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Binding\Http\RelatedFilterParameterBinding;
use App\Filter\Requirement\FieldFilterOperator;
use App\Model\Stop;
use App\Model\Track;
use App\Model\TrackStop;
use App\Provider\TrackRepository;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{provider}/track-stops', name: 'track_stops_')]
class TrackStopsController extends Controller
{
    /**
     * @OA\Tag(name="Track Stop")
     */
    #[Route(name: 'list', methods: ['GET'])]
    public function list(
        TrackRepository $trackRepository,
        #[ParameterBindingProvider([__CLASS__, 'getParameterBindings'])]
        array $requirements
    ): Response {
        $stops = $trackRepository->stops(...$requirements);

        return $this->json(
            $stops,
            context: $this->serializerContextFactory->create(TrackStop::class)
        );
    }

    public static function getParameterBindings(): ParameterBinding
    {
        return new ParameterBindingGroup(
            new RelatedFilterParameterBinding(
                parameter: 'stop',
                resource: Stop::class,
                documentation: [
                    'description' => 'Select only records related to the specified stop.',
                ]
            ),
            new RelatedFilterParameterBinding(
                parameter: 'track',
                resource: Track::class,
                documentation: [
                    'description' => 'Select only records related to the specified track.',
                ]
            ),
            new FieldFilterParameterBinding(
                parameter: 'order',
                field: 'order',
                documentation: fn (FieldFilterOperator $operator) => [
                    'description' => sprintf(
                        'Select only records where the position on the track %s specified value.',
                        FieldFilterParameterBinding::mapOperatorToDescription($operator)
                    ),
                    'schema' => [
                        'type'    => 'integer',
                        'minimum' => 0,
                    ],
                ],
                operators: FieldFilterParameterBinding::ORDINAL_OPERATORS
            ),
            new ImportFilterParameterBinding(),
            new LimitParameterBinding(),
        );
    }
}
