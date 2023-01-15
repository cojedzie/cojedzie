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
use App\Dto\Trip;
use App\Filter\Binding\Http\IdConstraintParameterBinding;
use App\Filter\Requirement\Embed;
use App\Filter\Requirement\IdConstraint;
use App\Provider\TripRepository;
use Symfony\Component\Routing\Annotation\Route;

#[Route(path: '/{provider}/trips', name: 'trip_')]
class TripController extends Controller
{
    #[Route(path: '/{id}', name: 'details', methods: ['GET'], options: ['version' => '1.0'])]
    public function one(
        #[IdConstraintParameterBinding(from: ['attributes'])]
        IdConstraint $id,
        TripRepository $repository
    ) {
        $trip = $repository->first(
            $id,
            new Embed('schedule')
        );

        return $this->json(
            data: $trip,
            context: $this->serializerContextFactory->create(Trip::class)
        );
    }
}
