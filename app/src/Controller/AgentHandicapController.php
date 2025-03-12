<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AgentHandicapController extends AbstractController
{
    #[Route('/agent/handicap', name: 'app_agent_handicap')]
    public function index(): Response
    {
        return $this->render('agent_handicap/index.html.twig', [
            'controller_name' => 'AgentHandicapController',
        ]);
    }
}
