<?php

namespace App\Controller;

use App\Provider\Provider;
use App\Service\ProviderResolver;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends Controller
{
    /**
     * @Route("/", name="choose")
     */
    public function choose(ProviderResolver $resolver)
    {
        return $this->render('choose.html.twig', ['providers' => $resolver->all()]);
    }

    /**
     * @Route("/{provider}/manifest.json", name="provider_manifest")
     * @Route("/manifest.json", name="main_manifest")
     */
    public function manifest(?Provider $provider = null)
    {
        $response = $this->render('manifest.json.twig', ['provider' => $provider]);
        $response->headers->set('Content-Type', 'application/json');

        return $response;
    }

    /**
     * @Route("/{provider}", name="app")
     */
    public function app(Provider $provider, Request $request)
    {
        $state = json_decode($request->query->get('state', '{}'), true) ?: [];
        $state = array_merge(
            [
                'version' => 1,
                'stops'   => [],
            ],
            $state
        );

        return $this->render('app.html.twig', compact('state', 'provider'));
    }
}
