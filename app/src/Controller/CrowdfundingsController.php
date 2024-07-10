<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class CrowdfundingsController extends AbstractController
{
    #[Route('/crowdfundings', name: 'app_crowdfundings')]
    public function index(): Response
    {
        return $this->render('crowdfundings/index.html.twig', [
            'controller_name' => 'CrowdfundingsController',
        ]);
    }
}
