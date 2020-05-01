<?php

namespace App\Controller\Api\v1;

use App\Controller\Controller;
use App\Service\Converter;
use App\Service\ProviderResolver;
use Swagger\Annotations as SWG;
use Symfony\Component\Routing\Annotation\Route;
use function Kadet\Functional\ref;

/**
 * @Route("/providers")
 * @SWG\Tag(name="Providers")
 */
class ProviderController extends Controller
{
    /**
     * @Route("/", methods={"GET"})
     */
    public function index(ProviderResolver $resolver, Converter $converter)
    {
        $providers = $resolver
            ->all()
            ->map(ref([$converter, 'convert']))
            ->values()
            ->toArray()
        ;
        return $this->json($providers);
    }
}
