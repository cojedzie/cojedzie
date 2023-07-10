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
use App\Exception\InvalidFormException;
use App\Form\CreateFederatedConnectionCommandType;
use App\Message\CheckConnectionMessage;
use App\Service\FederatedConnectionUpdateFactory;
use Carbon\Carbon;
use Doctrine\ORM\EntityManagerInterface;
use Nelmio\ApiDocBundle\Annotation\Model;
use OpenApi\Annotations as OA;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\DelayStamp;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used for managing resources related to the federation feature.
 *
 * @package App\Controller
 *
 * @OA\Tag(name="Federation")
 */
#[Route(path: '/federation', name: 'federation_')]
class FederationController extends Controller
{
    // fixme: add access control
    /**
     * Create new connection under some server.
     *
     * @OA\RequestBody(
     *     @OA\JsonContent(ref=@Model(type=CreateFederatedConnectionCommandType::class))
     * )
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
    public function connect(Request $request, EntityManagerInterface $manager, MessageBusInterface $bus)
    {
        $form = $this->createForm(
            CreateFederatedConnectionCommandType::class,
            new FederatedConnectionEntity()
        );

        $form->submit($request->request->all(), false);

        if (!$form->isSubmitted()) {
            throw new BadRequestException();
        }

        if (!$form->isValid()) {
            throw new InvalidFormException($form);
        }

        /** @var FederatedConnectionEntity $connection */
        $connection = $form->getData();
        $connection->setOpenedAt(Carbon::now());
        // Give this connection some time to get initialized
        $connection->setNextCheck(Carbon::now()->addSeconds(10));

        $manager->persist($connection);
        $manager->flush();

        $bus->dispatch(
            new CheckConnectionMessage($connection->getId()),
            [new DelayStamp(10000)]
        );

        return $this->json(
            ['connection_id' => $connection->getId()->toRfc4122()],
            Response::HTTP_CREATED
        );
    }

    /**
     * Mark specified connection as closed.
     *
     * @OA\Parameter(
     *     name="connection",
     *     description="Identifier of connection to be closed",
     *     in="path",
     *     example="a0b562d1-e0ce-4fc0-96e4-8d28a370a9e1",
     *     @OA\Schema(type="string", format="uuid")
     * )
     */
    #[Route(path: '/connections/{connection}', name: 'disconnect', methods: ['DELETE'], options: ['version' => '1.0'])]
    public function disconnect(
        Request $request,
        FederatedConnectionEntity $connection,
        EntityManagerInterface $manager,
        HubInterface $hub,
        FederatedConnectionUpdateFactory $updateFactory
    ) {
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
