<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Service\SerializerContextFactory;
use App\Service\StatusService;
use JMS\Serializer\SerializerInterface;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller responsible for sharing information about status of this particular node.
 *
 * @package App\Controller
 * @Route("/status", name="status_")
 *
 * @OA\Tag(name="Status")
 */
class StatusController extends Controller
{
    private StatusService $service;

    public function __construct(SerializerInterface $serializer, SerializerContextFactory $serializerContextFactory, StatusService $service)
    {
        parent::__construct($serializer, $serializerContextFactory);
        $this->service = $service;
    }

    /**
     * @Route("", name="aggregated", options={"version": "1.0"})
     */
    public function aggregated()
    {
        $aggregated = $this->service->getAggregatedStatus();

        return $this->json($aggregated);
    }

    /**
     * @Route("/endpoints", name="endpoints", options={"version": "1.0"})
     */
    public function endpoints()
    {
        $endpoints = $this->service->getEndpointsStatus();

        return $this->json($endpoints);
    }

    /**
     * @Route("/time", name="time", options={"version": "1.0"})
     */
    public function time()
    {
        $endpoints = $this->service->getTimeStatus();

        return $this->json($endpoints);
    }
}
