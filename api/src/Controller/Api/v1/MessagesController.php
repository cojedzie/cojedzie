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
use App\Model\Message;
use App\Provider\MessageRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{provider}/messages", name="message_")
 *
 * @OA\Tag(name="Messages")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
class MessagesController extends Controller
{
    /**
     * @Route("", name="all", methods={"GET"}, options={"version"="1.0"})
     *
     * @OA\Response(
     *     response=200,
     *     description="Returns all messages from carrier at given moment.",
     *     @OA\JsonContent(type="array", @OA\Items(ref=@Model(type=Message::class)))
     * )
     */
    public function all(MessageRepository $messages)
    {
        return $this->json($messages->getAll());
    }
}
