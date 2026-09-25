<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/metavers')]
class MetaversController extends AbstractController
{
    #[Route('/', name: 'app_metavers')]
    public function index(): Response
    {
        return $this->render('metavers/index.html.twig', [
            'controller_name' => 'MetaversController',
        ]);
    }

}
