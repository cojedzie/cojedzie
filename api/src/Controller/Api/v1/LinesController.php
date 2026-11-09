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

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Dto\CollectionResult;
use App\Dto\Line;
use App\Filter\Binding\Http\FieldFilterParameterBinding;
use App\Filter\Binding\Http\IdConstraintParameterBinding;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Binding\Http\ParameterBinding;
use App\Filter\Binding\Http\ParameterBindingGroup;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Filter\Requirement\IdConstraint;
use App\Filter\Requirement\Requirement;
use App\Provider\LineRepository;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{provider}/lines', name: 'line_')]
#[OA\Tag(name: 'Lines')]
#[OA\Parameter(ref: '#/components/parameters/provider')]
class LinesController extends Controller
{
    /**
     * List lines, by symbol in natural order with night lines last.
     *
     * @param list<Requirement> $requirements
     */
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.0'])]
    #[ParameterBindingProvider([self::class, 'getParameterBinding'])]
    #[OA\Response(
        response: 200,
        description: 'List of lines matching given criteria.',
        content: [
            new OA\MediaType(
                mediaType: 'application/vnd.cojedzie.collection+json',
                schema: new OA\Schema(ref: new Model(type: CollectionResult::class)),
            ),
            new OA\JsonContent(
                type: 'array',
                items: new OA\Items(ref: new Model(type: Line::class)),
            ),
        ],
    )]
    public function index(
        LineRepository $lineRepository,
        array $requirements
    ): Response {
        return $this->apiResponseFactory->createCollectionResponse($lineRepository->all(...$requirements));
    }

    /**
     * Get information about specific line.
     */
    #[Route(path: '/{line}', name: 'details', methods: ['GET'], options: ['version' => '1.0'])]
    #[OA\Response(
        response: 200,
        description: 'Line details.',
        content: new OA\MediaType(
            mediaType: 'application/vnd.cojedzie.line+json',
            schema: new OA\Schema(ref: new Model(type: Line::class)),
        ),
    )]
    #[OA\Response(response: 404, description: 'Line not found.')]
    public function one(
        LineRepository $lineRepository,
        #[IdConstraintParameterBinding(parameter: 'line', from: ['attributes'])]
        IdConstraint $id
    ): Response {
        $line = $lineRepository->first($id);

        if ($line === null) {
            throw new NotFoundHttpException('Line not found.');
        }

        return $this->apiResponseFactory->createResponse($line);
    }

    public static function getParameterBinding(): ParameterBinding
    {
        return new ParameterBindingGroup(
            new IdConstraintParameterBinding(
                isMultiple: true,
                documentation: [
                    'description' => 'Line unique identifiers as provided by data provider.',
                ]
            ),
            new LimitParameterBinding(defaultLimit: 1000, maxLimit: 1000),
            new FieldFilterParameterBinding(
                parameter: 'symbol',
                field: 'symbol',
                defaultOperator: FieldFilterOperator::BeginsWith,
                operators: FieldFilterParameterBinding::STRING_OPERATORS,
                options: [
                    FieldFilter::OPTION_CASE_SENSITIVE => false,
                ],
                documentation: [
                    'description' => 'Beginning of the line symbol, e.g. "1" finds 1, 10 and 111.',
                    'schema'      => [
                        'type' => 'string',
                    ],
                ]
            ),
        );
    }
}
