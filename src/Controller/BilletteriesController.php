<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class BilletteriesController extends AbstractController
{
    #[Route('/billetteries', name: 'app_billetteries')]
    public function index(): Response
    {
        return $this->render('billetteries/index.html.twig', [
            'controller_name' => 'BilletteriesController',
            'currentDate' => new \DateTime(),
        ]);
    }
}
