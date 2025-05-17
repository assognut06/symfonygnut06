<?php

// src/Controller/FooterController.php
namespace App\Controller;

use App\Repository\EntrepriseRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LogoEntrepriseController extends AbstractController
{
    #[Route('/_partials/footer-logos', name: 'footer_logos')]
    public function logos(EntrepriseRepository $entrepriseRepository): Response
    {
        $entreprises = $entrepriseRepository->findAll();

        return $this->render('_partials/logos_entreprises.html.twig', [
            'entreprises' => $entreprises
        ]);
    }
}
