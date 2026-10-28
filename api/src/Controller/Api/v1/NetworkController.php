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
use App\Service\ApiResponseFactory;
use App\Service\StatusService;
use Kadet\Functional\Collections as c;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\Authorization;
use Symfony\Component\Mercure\Discovery;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Uid\NilUuid;

/**
 * Controller used for managing resources related to the federation feature.
 */
#[Route(path: '/network', name: 'network_', options: ['sentry_trace_sample' => 0.01])]
#[OA\Tag(name: 'Network')]
class NetworkController extends Controller
{
    public function __construct(
        ApiResponseFactory $apiResponseFactory,
        private readonly StatusService $status,
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
        parent::__construct($apiResponseFactory);
    }

    #[Route(path: '/nodes', name: 'nodes', methods: ['GET'], options: ['version' => '1.0'])]
    #[OA\Response(response: 200, description: 'Nodes that are currently available in the network.', content: new OA\JsonContent(ref: new Model(type: Node::class)))]
    public function nodes(
        FederatedConnectionEntityRepository $connectionRepository,
        Converter $converter,
        Discovery $discovery,
        Authorization $authorization,
        Request $request
    ): Response {
        $discovery->addLink($request);

        $nodes = $connectionRepository->findAllReadyConnections()
            |> c\map(fn ($connection) => $converter->convert($connection, Dto::class))
            |> c\prepend($this->getSelfNode())
            |> c\toSeq();

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
            // Node still holds an Illuminate collection until its property becomes a Seq.
            'endpoints' => collect($this->status->getEndpointsStatus()->toArray()),
            'url'       => $baseUrl,
            'type'      => Node::TYPE_HUB,
        ]);
    }
}
