<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class Handi3DController extends AbstractController
{
    #[Route('/Handi-3D', name: 'handi_3d_index')]
    public function index(): Response
    {
        return $this->render('hand_3d/index.html.twig', [
            'title' => 'Handi-3D',
            'controller_name' => 'Hand3DController',
        ]);
    }

    #[Route('/Handi-3D/viewer', name: 'handi_3d_viewer')]
    public function viewer(): Response
    {
        return $this->render('hand_3d/viewer.html.twig', [
            'title' => 'Visualiseur 3D',
            'controller_name' => 'Hand3DController',
        ]);
    }

    #[Route('/Handi-3D/gallery', name: 'handi_3d_gallery')]
    public function gallery(): Response
    {
        return $this->render('hand_3d/gallery.html.twig', [
            'title' => 'Galerie 3D',
            'controller_name' => 'Hand3DController',
        ]);
    }
}