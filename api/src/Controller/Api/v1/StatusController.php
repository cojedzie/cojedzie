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
     * @Route("", name="aggregated", methods={"GET"}, options={"version": "1.0"})
     */
    public function aggregated()
    {
        $aggregated = $this->service->getAggregatedStatus();

        return $this->json($aggregated);
    }

    /**
     * @Route("/endpoints", name="endpoints", methods={"GET"}, options={"version": "1.0"})
     */
    public function endpoints()
    {
        $endpoints = $this->service->getEndpointsStatus();

        return $this->json($endpoints);
    }

    /**
     * @Route("/time", name="time", methods={"GET"}, options={"version": "1.0"})
     */
    public function time()
    {
        $endpoints = $this->service->getTimeStatus();

        return $this->json($endpoints);
    }

    /**
     * @Route("/health", name="health", methods={"GET"}, options={"version": "1.0"})
     */
    public function health()
    {
        return $this->json(['health' => 'alive']);
    }
}
