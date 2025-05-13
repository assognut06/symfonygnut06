<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DroitalimageController extends AbstractController
{
    #[Route('/droitalimage', name: 'app_droitalimage')]
    public function index(): Response
    {
        return $this->render('droitalimage/index.html.twig', [
            'controller_name' => 'DroitalimageController',
        ]);
    }
}
