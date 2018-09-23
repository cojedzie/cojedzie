<?php

namespace App\Controller;


use App\Provider\Provider;
use App\Service\ProviderResolver;
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
     * @Route("/{provider}", name="app")
     */
    public function app(Provider $provider)
    {
        return $this->render('app.html.twig', ['provider' => $provider]);
    }

    /**
     * @Route("/{provider}/manifest.json", name="webapp_manifest")
     */
    public function manifest(Provider $provider)
    {
        return $this->render('manifest.json.twig', ['provider' => $provider]);
    }
}