<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdhesionController extends AbstractController
{
    #[Route('/adhesion', name: 'app_adhesion')]
    public function index(): Response
    {
        return $this->render('adhesion/index.html.twig', [
            'controller_name' => 'AdhesionController',
            'currentDate' => new \DateTime(),
        ]);
    }
}
