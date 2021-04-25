<?php


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
 * @Route("/{provider}/departures")
 * @OA\Tag(name="Departures")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
class DeparturesController extends Controller
{
    /**
     * @Route("/{stop}", methods={"GET"})
     * @OA\Response(
     *     description="Gets departures from particular stop.",
     *     response=200,
     *     @OA\Schema(type="array", @OA\Items(ref=@Model(type=Departure::class)))
     * )
     */
    public function stop(DepartureRepository $departures, StopRepository $stops, $stop, Request $request)
    {
        $stop = $stops->first(new IdFilter($stop));

        return $this->json($departures->current(collect($stop), ...$this->getModifiersFromRequest($request)));
    }

    /**
     * @Route("/", methods={"GET"})
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
