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

namespace App\Controller;


use App\Service\SerializerContextFactory;
use JMS\Serializer\SerializerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class Controller extends AbstractController
{
    protected $serializer;
    protected $serializerContextFactory;

    public function __construct(SerializerInterface $serializer, SerializerContextFactory $serializerContextFactory)
    {
        $this->serializer = $serializer;
        $this->serializerContextFactory = $serializerContextFactory;
    }

    protected function json($data, int $status = 200, array $headers = [], $context = null): JsonResponse
    {
        return new JsonResponse($this->serializer->serialize($data, "json", $context), $status, $headers, true);
    }
}
