<?php

namespace App\Controller\Api\v1;

use App\Repository\FederatedConnectionEntityRepository;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller used for managing resources related to the federation feature.
 *
 * @package App\Controller
 * @Route("/network", name="network_")
 *
 * @OA\Tag(name="Network")
 */
class NetworkController
{
    /**
     * @Route("/nodes", name="nodes", options={"version": "1.0"})
     */
    public function nodes(FederatedConnectionEntityRepository $connectionRepository)
    {

    }
}
