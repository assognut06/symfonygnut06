<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class TooltipController extends AbstractController
{
    #[Route('/tooltip', name: 'app_tooltip')]
    public function index(): Response
    {
        return $this->render('tooltip/index.html.twig', [
            'controller_name' => 'TooltipController',
        ]);
    }
}
