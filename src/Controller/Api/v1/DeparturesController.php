<?php


namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Departure;
use App\Provider\DepartureRepository;
use App\Provider\StopRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Class DeparturesController
 *
 * @Route("/departures")
 * @SWG\Tag(name="Departures")
 * @SWG\Parameter(ref="#/parameters/provider")
 */
class DeparturesController extends Controller
{
    /**
     * @Route("/{stop}", methods={"GET"})
     * @SWG\Response(
     *     description="Gets departures from particular stop.",
     *     response=200,
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Departure::class)))
     * )
     */
    public function stop(DepartureRepository $departures, StopRepository $stops, $stop)
    {
        $stop = $stops->getById($stop);

        return $this->json($departures->getForStop($stop));
    }

    /**
     * @Route("/", methods={"GET"})
     * @SWG\Response(
     *     description="Gets departures from given stops.",
     *     response=200,
     *     @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Departure::class)))
     * )
     *
     * @SWG\Parameter(
     *     name="stop",
     *     description="Stop identifiers.",
     *     type="array",
     *     in="query",
     *     @SWG\Items(type="string")
     * )
     *
     * @SWG\Parameter(
     *     name="limit",
     *     description="Max departures count",
     *     type="integer",
     *     in="query"
     * )
     */
    public function stops(DepartureRepository $departures, StopRepository $stops, Request $request)
    {
        $stops = $stops->getManyById($request->query->get('stop'))
            ->flatMap([ $departures, 'getForStop' ])
            ->sortBy(function (Departure $departure) {
                return $departure->getEstimated();
            });

        return $this->json($stops->values()->slice(0, (int)$request->query->get('limit', 8)));
    }
}
