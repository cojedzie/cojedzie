<?php

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
