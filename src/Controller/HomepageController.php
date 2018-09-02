<?php

namespace App\Controller;


use Symfony\Component\Routing\Annotation\Route;

class HomepageController extends Controller
{
    /**
     * @Route("/", name="home")
     */
    public function homepage()
    {
        return $this->render('base.html.twig');
    }
}