<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\DataConverter\Converter;
use App\Model\Federation\Node;
use App\Repository\FederatedConnectionEntityRepository;
use App\Service\SerializerContextFactory;
use App\Service\StatusService;
use JMS\Serializer\SerializerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\NilUuid;
use function Kadet\Functional\ref;

/**
 * Controller used for managing resources related to the federation feature.
 *
 * @package App\Controller
 * @Route("/network", name="network_")
 *
 * @OA\Tag(name="Network")
 */
class NetworkController extends Controller
{
    private StatusService $status;
    private UrlGeneratorInterface $urlGenerator;

    public function __construct(
        SerializerInterface $serializer,
        SerializerContextFactory $serializerContextFactory,
        StatusService $status,
        UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($serializer, $serializerContextFactory);

        $this->status = $status;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @Route("/nodes", name="nodes", methods={"GET"}, options={"version": "1.0"})
     *
     * @OA\Response(
     *     response=200,
     *     description="Nodes that are currently available in the network.",
     *     @OA\JsonContent(ref=@Model(type=Node::class))
     * )
     */
    public function nodes(
        FederatedConnectionEntityRepository $connectionRepository,
        Converter $converter
    ) {
        $nodes = collect($connectionRepository->findAllReadyConnections())->map(ref([$converter, 'convert']));

        $nodes->prepend($this->getSelfNode());

        return $this->json($nodes);
    }

    private function getSelfNode()
    {
        $context = $this->urlGenerator->getContext();
        $baseUrl = sprintf("%s://%s%s", $context->getScheme(), $context->getHost(), $context->getBaseUrl());

        return Node::createFromArray([
            'id'        => new NilUuid(),
            'endpoints' => $this->status->getEndpointsStatus(),
            'url'       => $baseUrl,
            'type'      => Node::TYPE_HUB,
        ]);
    }
}
