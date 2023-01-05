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
use App\DataConverter\Converter;
use App\Dto\Dto;
use App\Dto\Federation\Node;
use App\Repository\FederatedConnectionEntityRepository;
use App\Service\SerializerContextFactory;
use App\Service\StatusService;
use JMS\Serializer\SerializerInterface;
use Kadet\Functional as f;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\NilUuid;

/**
 * Controller used for managing resources related to the federation feature.
 *
 * @package App\Controller
 *
 * @OA\Tag(name="Network")
 */
#[Route(path: '/network', name: 'network_')]
class NetworkController extends Controller
{
    public function __construct(
        SerializerInterface $serializer,
        SerializerContextFactory $serializerContextFactory,
        private readonly StatusService $status,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($serializer, $serializerContextFactory);
    }

    /**
     * @OA\Response(
     *     response=200,
     *     description="Nodes that are currently available in the network.",
     *     @OA\JsonContent(ref=@Model(type=Node::class))
     * )
     */
    #[Route(path: '/nodes', name: 'nodes', methods: ['GET'], options: ['version' => '1.0'])]
    public function nodes(
        FederatedConnectionEntityRepository $connectionRepository,
        Converter $converter,
        Discovery $discovery,
        Authorization $authorization,
        Request $request
    ): Response {
        $discovery->addLink($request);

        $nodes = collect($connectionRepository->findAllReadyConnections())
            ->map(f\partial(f\ref([$converter, 'convert']), f\_, Dto::class))
            ->prepend($this->getSelfNode())
        ;

        $response = $this->json($nodes);
        $response->headers->setCookie(
            $authorization->createCookie($request, ['network/nodes'])
        );

        return $response;
    }

    private function getSelfNode()
    {
        $context = $this->urlGenerator->getContext();

        $port = '';
        if ($context->getScheme() === 'http' && $context->getHttpPort() !== 80) {
            $port = sprintf(':%d', $context->getHttpPort());
        } elseif ($context->getScheme() === 'https' && $context->getHttpsPort() !== 443) {
            $port = sprintf(':%d', $context->getHttpsPort());
        }

        $baseUrl = sprintf("%s://%s%s%s", $context->getScheme(), $context->getHost(), $port, $context->getBaseUrl());

        return Node::createFromArray([
            'id'        => new NilUuid(),
            'endpoints' => $this->status->getEndpointsStatus(),
            'url'       => $baseUrl,
            'type'      => Node::TYPE_HUB,
        ]);
    }
}
