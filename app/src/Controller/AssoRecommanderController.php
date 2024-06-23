<?php

namespace App\Controller;

use App\Entity\AssoRecommander;
use App\Form\AssoRecommanderType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;

class AssoRecommanderController extends AbstractController
{
    #[Route('/asso/recommander', name: 'app_asso_recommander')]
    public function index(): Response
    {
        return $this->render('asso_recommander/index.html.twig', [
            'controller_name' => 'AssoRecommanderController',
        ]);
    }

    #[Route('/asso/recommander/new', name: 'app_asso_recommander_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $assoRecommander = new AssoRecommander();
        $form = $this->createForm(AssoRecommanderType::class, $assoRecommander);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($assoRecommander);
            $entityManager->flush();

            return $this->redirectToRoute('home');
        }

        return $this->render('asso_recommander/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
