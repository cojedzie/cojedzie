<?php

namespace App\Controller\Api\v1;


use App\Controller\Controller;
use App\Provider\MessageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/messages")
 */
class MessagesController extends Controller
{
    /**
     * @Route("/", methods={"GET"})
     */
    public function all(MessageRepository $messages)
    {
        return $this->json($messages->getAll());
    }
}