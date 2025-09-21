<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BenevoleJnaController extends AbstractController
{
    #[Route('/benevole/jna', name: 'app_benevole_jna')]
    public function index(): Response
    {
        return $this->render('benevole_jna/index.html.twig', [
            'controller_name' => 'BenevoleJnaController',
        ]);
    }
}
