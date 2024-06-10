<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AssociationsNiceController extends AbstractController
{
    #[Route('/associationsNice', name: 'app_associations_nice')]
    public function index(): Response
    {
        return $this->render('associations_nice/index.html.twig', [
            'controller_name' => 'AssociationsNiceController',
            'currentDate' => new \DateTime(),
        ]);
    }
}
