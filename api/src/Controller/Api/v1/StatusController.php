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
use App\Service\ApiResponseFactory;
use App\Service\StatusService;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller responsible for sharing information about status of this particular node.
 *
 * @package App\Controller
 *
 * @OA\Tag(name="Status")
 */
#[Route(path: '/status', name: 'status_')]
class StatusController extends Controller
{
    public function __construct(
        ApiResponseFactory $apiResponseFactory,
        private readonly StatusService $service
    ) {
        parent::__construct($apiResponseFactory);
    }

    #[Route(path: '', name: 'aggregated', methods: ['GET'], options: ['version' => '1.1'])]
    public function aggregated(): Response
    {
        $aggregated = $this->service->getAggregatedStatus();
        return $this->json($aggregated);
    }

    #[Route(path: '/endpoints', name: 'endpoints', methods: ['GET'], options: ['version' => '1.0'])]
    public function endpoints(): Response
    {
        $endpoints = $this->service->getEndpointsStatus();
        return $this->json($endpoints);
    }

    #[Route(path: '/time', name: 'time', methods: ['GET'], options: ['version' => '1.0'])]
    public function time(): Response
    {
        $time = $this->service->getTimeStatus();
        return $this->json($time);
    }

    #[Route(path: '/version', name: 'version', methods: ['GET'], options: ['version' => '1.0'])]
    public function version(): Response
    {
        $version = $this->service->getVersionStatus();
        return $this->json($version);
    }

    #[Route(path: '/health', name: 'health', methods: ['GET'], options: ['version' => '1.0'])]
    public function health(): Response
    {
        return $this->json(['health' => 'alive']);
    }
}
