<?php

namespace App\Controller;


use App\Provider\MessageRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

/**
 * @Route("/{provider}/messages")
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