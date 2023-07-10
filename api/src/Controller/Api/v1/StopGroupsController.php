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
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @OA\Tag(name="Stops")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
#[Route(path: '/{provider}/stop-groups', name: 'stop_group_')]
class StopGroupsController extends Controller
{
    /**
     * List stop groups.
     *
     * @OA\Response(
     *     response=200,
     *     description="List of stop groups.",
     *     @OA\MediaType(
     *          mediaType="application/vnd.cojedzie.collection+json",
     *          @OA\Schema(ref=@Model(type=CollectionResult::class))
     *     ),
     *     @OA\Schema(type="array", @OA\Items(ref=@Model(type=StopGroup::class)))
     * )
     *
     * @psalm-param iterable<Requirement> $requirements
     */
    #[Route(path: '', name: 'list', methods: ['GET'], options: ['version' => '1.0'])]
    #[ParameterBindingProvider([StopsController::class, 'getParameterBinding'])]
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
