<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use OpenApi\Annotations as OA;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Controller responsible for sharing information about status of this particular node.
 *
 * @package App\Controller
 * @Route("/status", name="status_")
 *
 * @OA\Tag(name="Status")
 */
class HealthController extends Controller
{
    /**
     * @Route("", name="aggregated", options={"version": "1.0"})
     */
    public function aggregated()
    {
        return $this->json(['status' => 'ok']);
    }
}
