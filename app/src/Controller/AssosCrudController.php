<?php

namespace App\Controller;

use App\Entity\AssoRecommander;
use App\Form\AssoRecommander1Type;
use App\Repository\AssoRecommanderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/assos/crud')]
class AssosCrudController extends AbstractController
{
    #[Route('/', name: 'app_assos_crud_index', methods: ['GET'])]
    public function index(AssoRecommanderRepository $assoRecommanderRepository): Response
    {
        return $this->render('assos_crud/index.html.twig', [
            'asso_recommanders' => $assoRecommanderRepository->findAll(),
            'loading' => false,
        ]);
    }

    #[Route('/new', name: 'app_assos_crud_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $assoRecommander = new AssoRecommander();
        $form = $this->createForm(AssoRecommander1Type::class, $assoRecommander);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($assoRecommander);
            $entityManager->flush();

            return $this->redirectToRoute('app_assos_crud_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('assos_crud/new.html.twig', [
            'asso_recommander' => $assoRecommander,
            'form' => $form,
            'loading' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_assos_crud_show', methods: ['GET'])]
    public function show(AssoRecommander $assoRecommander): Response
    {
        return $this->render('assos_crud/show.html.twig', [
            'asso_recommander' => $assoRecommander,
            'loading' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_assos_crud_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, AssoRecommander $assoRecommander, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(AssoRecommander1Type::class, $assoRecommander);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_assos_crud_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('assos_crud/edit.html.twig', [
            'asso_recommander' => $assoRecommander,
            'form' => $form,
            'loading' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_assos_crud_delete', methods: ['POST'])]
    public function delete(Request $request, AssoRecommander $assoRecommander, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$assoRecommander->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($assoRecommander);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_assos_crud_index', [], Response::HTTP_SEE_OTHER);
    }
}
