<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NiceFeteSaRentreeController extends AbstractController
{
    #[Route('/gnut_06/nice_fete_sa_rentree', name: 'app_nice_fete_sa_rentree')]
    public function index(): Response
    {
        return $this->render('nice_fete_sa_rentree/index.html.twig', [
            'controller_name' => 'NiceFeteSaRentreeController',
        ]);
    }
}
