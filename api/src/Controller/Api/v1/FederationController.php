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
use App\Entity\Federation\FederatedConnectionEntity;
use App\Entity\Federation\FederatedServerEntity;
use App\Exception\InvalidFormException;
use App\Form\CreateFederatedConnectionCommandType;
use App\Message\CheckFederatedConnection;
use App\Service\FederatedConnectionUpdateFactory;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Attribute\Model;
use OpenApi\Attributes as OA;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\CurrentUser;

/**
 * Controller used for managing resources related to the federation feature.
 */
#[Route(path: '/federation', name: 'federation_')]
#[OA\Tag(name: 'Federation')]
class FederationController extends Controller
{
    /**
     * Create new connection for the authenticated server.
     */
    #[Route(
        path: '/connections',
        name: 'connect',
        methods: ['POST'],
        options: [
            'version'             => '1.0',
            'sentry_trace_sample' => 0.1,
        ]
    )]
    #[OA\RequestBody(content: new OA\JsonContent(ref: new Model(type: CreateFederatedConnectionCommandType::class)))]
    public function connect(
        #[CurrentUser]
        FederatedServerEntity $server,
        Request $request,
        EntityManagerInterface $manager,
        MessageBusInterface $bus
    ): JsonResponse {
        $connection = new FederatedConnectionEntity();
        $connection->setServer($server);

        $form = $this->createForm(CreateFederatedConnectionCommandType::class, $connection);

        $form->submit($request->request->all());

        if (!$form->isSubmitted()) {
            throw new BadRequestException();
        }

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        if (!$server->allows($connection->getUrl())) {
            throw new AccessDeniedHttpException(sprintf('Server %s cannot connect at this URL.', $server->getUserIdentifier()));
        }

        $connection->setOpenedAt(Carbon::now());
        // Give this connection some time to get initialized
        $connection->setNextCheck(Carbon::now()->addSeconds(10));

        $manager->persist($connection);
        $manager->flush();

        $bus->dispatch(
            new CheckFederatedConnection($connection->getId()),
            [new DelayStamp(10000)]
        );

        return $this->json(
            ['connection_id' => $connection->getId()->toRfc4122()],
            Response::HTTP_CREATED
        );
    }

    /**
     * Mark specified connection as closed.
     */
    #[Route(path: '/connections/{connection}', name: 'disconnect', methods: ['DELETE'], options: ['version' => '1.0'])]
    #[OA\Parameter(name: 'connection', description: 'Identifier of connection to be closed', in: 'path', example: 'a0b562d1-e0ce-4fc0-96e4-8d28a370a9e1', schema: new OA\Schema(type: 'string', format: 'uuid'))]
    public function disconnect(
        #[MapEntity(id: 'connection')]
        FederatedConnectionEntity $connection,
        EntityManagerInterface $manager,
        HubInterface $hub,
        FederatedConnectionUpdateFactory $updateFactory
    ): JsonResponse {
        if (in_array(
            $connection->getState(),
            [
                FederatedConnectionEntity::STATE_READY,
                FederatedConnectionEntity::STATE_SUSPENDED,
            ]
        )) {
            $hub->publish($updateFactory->createNodeLeftUpdate($connection));
        }

        $connection->setState(FederatedConnectionEntity::STATE_CLOSED);
        $connection->setClosedAt(Carbon::now());

        $manager->persist($connection);
        $manager->flush();

        return $this->json(['connection_id' => $connection->getId()->toRfc4122()]);
    }
}
