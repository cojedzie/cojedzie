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
     */
    public function stops(DepartureRepository $departures, StopRepository $stops, Request $request)
    {
        $stops = collect($request->query->get('stop'))
            ->map([ $stops, 'getById' ])
            ->flatMap([ $departures, 'getForStop' ])
            ->sortBy(function (Departure $departure) {
                return $departure->getEstimated();
            });

        return $this->json($stops->values());
    }
}