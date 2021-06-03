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
