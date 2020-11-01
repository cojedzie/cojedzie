<?php

namespace App\Controller\Api\v1;


use App\Controller\Controller;
use App\Provider\MessageRepository;
use Nelmio\ApiDocBundle\Annotation\Model;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use App\Model\Message;

/**
 * @Route("/messages")
 * @SWG\Tag(name="Messages")
 * @SWG\Parameter(ref="#/parameters/provider")
 */
class MessagesController extends Controller
{
    /**
     * @SWG\Response(response=200, description="Returns all messages from carrier at given moment.", @SWG\Schema(type="array", @SWG\Items(ref=@Model(type=Message::class))))
     * @Route("/", methods={"GET"})
     */
    public function all(MessageRepository $messages)
    {
        return $this->json($messages->getAll());
    }
}