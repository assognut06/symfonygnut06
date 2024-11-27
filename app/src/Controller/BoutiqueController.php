<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/boutique')]
class BoutiqueController extends AbstractController
{
    #[Route('/', name: 'app_boutique')]
    public function index(): Response
    {
        return $this->render('boutique/index.html.twig', [
            'controller_name' => 'BoutiqueController',
        ]);
    }
    #[Route('/innova', name: 'app_innova')]
    public function innova(): Response
    {
        return $this->render('innova/index.html.twig', [
            'controller_name' => 'InnovaController',
        ]);
    }
}
