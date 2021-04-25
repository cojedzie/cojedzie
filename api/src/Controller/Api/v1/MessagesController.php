<?php

namespace App\Controller\Api\v1;


use App\Controller\Controller;
use App\Model\Message;
use App\Provider\MessageRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route("/{provider}/messages")
 * @OA\Tag(name="Messages")
 * @OA\Parameter(ref="#/components/parameters/provider")
 */
class MessagesController extends Controller
{
    /**
     * @OA\Response(
     *     response=200,
     *     description="Returns all messages from carrier at given moment.",
     *     @OA\Schema(type="array", @OA\Items(ref=@Model(type=Message::class)))
     * )
     *
     * @Route("/", methods={"GET"})
     */
    public function all(MessageRepository $messages)
    {
        return $this->json($messages->getAll());
    }
}
