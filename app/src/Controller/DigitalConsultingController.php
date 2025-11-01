<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class DigitalConsultingController extends AbstractController
{
    #[Route('/digital/consulting', name: 'app_digital_consulting')]
    public function index(): Response
    {
        return $this->render('digital_consulting/index.html.twig', [
            'controller_name' => 'DigitalConsultingController',
        ]);
    }
}
