<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class NewsletterConfirmationController extends AbstractController
{
    #[Route('/newsletter/confirmation', name: 'app_newsletter_confirmation', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('newsletter/confirmation.html.twig');
    }
}
