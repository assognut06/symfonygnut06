<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Repository\TihRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tih')]
class TihSearchController extends AbstractController
{
    #[Route('/tih_search', name: 'app_tih_search')]
    public function index(TihRepository $tihRepository): Response
    {
        $tihs = $tihRepository->findAll();

        return $this->render('tih_search/index.html.twig', [
            'tihs' => $tihs,
        ]);
    }
}
