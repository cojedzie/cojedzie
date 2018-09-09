<?php


namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Departure;
use App\Provider\DepartureRepository;
use App\Provider\StopRepository;
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
     * @Route("/{id}")
     */
    public function stop(DepartureRepository $departures, StopRepository $stops, $id)
    {
        $stop = $stops->getById($id);

        return $this->json($departures->getForStop($stop));
    }

    /**
     * @Route("/")
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