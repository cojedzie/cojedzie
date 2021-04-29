<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Trip;
use App\Modifier\IdFilter;
use App\Modifier\With;
use App\Provider\TripRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{provider}/trips", name="trip_")
 */
class TripController extends Controller
{
    /**
     * @Route("/{id}", name="details", methods={"GET"}, options={"version": "1.0"})
     */
    public function one($id, TripRepository $repository)
    {
        $trip = $repository->first(new IdFilter($id), new With('schedule'));

        return $this->json($trip, Response::HTTP_OK, [], $this->serializerContextFactory->create(Trip::class));
    }
}
