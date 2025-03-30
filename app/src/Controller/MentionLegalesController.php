<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MentionLegalesController extends AbstractController
{
    #[Route('/mention/legales', name: 'app_mention_legales')]
    public function index(): Response
    {
        return $this->render('mention_legales/index.html.twig', [
            'controller_name' => 'MentionLegalesController',
        ]);
    }
}
