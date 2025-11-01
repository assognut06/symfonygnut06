<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class GNUT06ParticipeAlaJourneeNationaleDesAidantsController extends AbstractController
{
    #[Route('/gnut_06/participe_ala_journee_nationale_des_aidants', name: 'appgnut_06_participe_ala_journee_nationale_des_aidants')]
    public function index(): Response
    {
        return $this->render('gnut06_participe_ala_journee_nationale_des_aidants/index.html.twig', [
            'controller_name' => 'GNUT06PparticipeAlaJourneeNationaleDesAidantsController',
        ]);
    }
}
