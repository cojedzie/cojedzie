<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Entity\Federation\FederatedConnectionEntity;
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
 * @Route("/federation", name="federation_")
 *
 * @OA\Tag(name="Federation")
 */
class FederationController extends Controller
{
    // fixme: add access control

    /**
     * Create new connection under some server.
     *
     * @Route("/connections", name="connect", methods={"POST"}, options={"version"="1.0"})
     *
     * @OA\RequestBody(
     *     @OA\JsonContent(ref=@Model(type=CreateFederatedConnectionCommandType::class))
     * )
     */
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

        // fixme: add validation

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
     * @Route("/connections/{connection}", name="disconnect", methods={"DELETE"}, options={"version"="1.0"})
     *
     * @OA\Parameter(
     *     name="connection",
     *     description="Identifier of connection to be closed",
     *     in="path",
     *     example="a0b562d1-e0ce-4fc0-96e4-8d28a370a9e1",
     *     @OA\Schema(type="string", format="uuid")
     * )
     */
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
