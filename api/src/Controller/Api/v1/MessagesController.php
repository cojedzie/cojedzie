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
use App\Dto\Message;
use App\Exception\NonReachableException;
use App\Filter\Binding\Http\EmbedParameterBinding;
use App\Filter\Binding\Http\FieldFilterParameterBinding;
use App\Filter\Binding\Http\LimitParameterBinding;
use App\Filter\Binding\Http\ParameterBinding;
use App\Filter\Binding\Http\ParameterBindingGroup;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Requirement\FieldFilter;
use App\Filter\Requirement\FieldFilterOperator;
use App\Provider\MessageRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @OA\Tag(name="Messages")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
#[Route(path: '/{provider}/messages', name: 'message_')]
class MessagesController extends Controller
{
    /**
     * Obtain messages.
     *
     * @OA\Response(
     *     response=200,
     *     description="List of messages valid at time of request",
     *     @OA\MediaType(
     *          mediaType="application/vnd.cojedzie.collection+json",
     *          @OA\Schema(ref=@Model(type=CollectionResult::class))
     *     ),
     *     @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Message::class)))
     * )
     */
    #[Route(path: '', name: 'all', methods: ['GET'], options: ['version' => '1.1'])]
    #[ParameterBindingProvider([self::class, 'getParameterBinding'])]
    public function all(
        MessageRepository $messageRepository,
        iterable $requirements
    ): Response {
        $messages = $messageRepository->all(...$requirements);

        return $this->apiResponseFactory->createCollectionResponse($messages);
    }

    /**
     * @psalm-return ParameterBinding[]
     */
    public static function getParameterBinding(): ParameterBinding
    {
        $typeFilterSchema = [
            'type' => 'string',
            'enum' => Message::TYPES,
        ];

        return new ParameterBindingGroup(
            new LimitParameterBinding(),
            new EmbedParameterBinding(['$refs.lines', '$refs.stops']),
            new FieldFilterParameterBinding(
                parameter: 'type',
                field: 'type',
                defaultOperator: FieldFilterOperator::Equals,
                operators: [
                    ...FieldFilterParameterBinding::EQUALITY_OPERATORS,
                    ...FieldFilterParameterBinding::SET_OPERATORS,
                ],
                options: [
                    FieldFilter::OPTION_CASE_SENSITIVE => false,
                ],
                documentation: fn (FieldFilterOperator $op) => [
                    'description' => sprintf(
                        'Select only messages with type %s.',
                        match ($op) {
                            FieldFilterOperator::Equals    => 'equal to specified value',
                            FieldFilterOperator::NotEquals => 'not equal to specified value',
                            FieldFilterOperator::In        => 'equal to one of specified values',
                            FieldFilterOperator::NotIn     => 'not equal to any of specified values',
                            default                        => throw new NonReachableException(),
                        }
                    ),
                    'schema' => $op->isSetOperator()
                        ? [
                            'type'  => 'array',
                            'items' => $typeFilterSchema,
                        ]
                        : $typeFilterSchema,
                ]
            ),
        );
    }
}
