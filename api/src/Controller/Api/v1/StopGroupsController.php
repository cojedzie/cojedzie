<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Dto\CollectionResult;
use App\Dto\Stop;
use App\Dto\StopGroup;
use App\Filter\Binding\Http\ParameterBindingProvider;
use App\Filter\Requirement\Requirement;
use App\Provider\StopRepository;
use Illuminate\Support\Collection;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(path: '/{provider}/stop-groups', name: 'stop_group_')]
#[OA\Tag(name: 'Stops')]
#[OA\Parameter(ref: '#/components/parameters/provider')]
class StopGroupsController extends Controller
{
    /**
     * List stop groups.
     *
     * @psalm-param iterable<Requirement> $requirements
     */
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.0'])]
    #[ParameterBindingProvider([StopsController::class, 'getParameterBinding'])]
    #[OA\Response(response: 200, description: 'List of stop groups.', content: [new OA\JsonContent(type: 'array', items: new OA\Items(ref: new Model(type: StopGroup::class))), new OA\MediaType(mediaType: 'application/vnd.cojedzie.collection+json', schema: new OA\Schema(ref: new Model(type: CollectionResult::class)))])]
    public function groups(
        StopRepository $stopRepository,
        iterable $requirements
    ): Response {
        $groups = static::group(
            $stopRepository->all(...$requirements)
        )->toArray();

        return $this->apiResponseFactory->createCollectionResponse(
            $groups,
            context: [
                'groups' => $this->apiResponseFactory->extractGroupsFromRequirements($requirements),
            ]
        );
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
}
