<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Model\Trip;
use App\Provider\TripRepository;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/trips")
 */
class TripController extends Controller
{
    /**
     * @Route("/{id}", methods={"GET"})
     */
    public function one($id, TripRepository $repository)
    {
        $trip = $repository->getById($id);

        return $this->json($trip, Response::HTTP_OK, [], $this->serializerContextFactory->create(Trip::class));
    }
}
