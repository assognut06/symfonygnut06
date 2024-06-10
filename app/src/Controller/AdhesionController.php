<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Twig\Environment;

class AdhesionController extends AbstractController
{
    private $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    #[Route('/adhesion', name: 'app_adhesion')]
    public function index(): Response
    {
        // Get the current date
        $currentDate = new \DateTime();

        // Validate the date
        if (!$currentDate) {
            throw new \Exception('Failed to get the current date');
        }

        // Render the view
        return new Response($this->twig->render('adhesion/index.html.twig', [
            'controller_name' => 'AdhesionController',
            'currentDate' => $currentDate,
        ]));
    }
}
