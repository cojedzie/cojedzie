<?php


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
