<?php

namespace App\Controller;

use App\Entity\Payers;
use App\Form\PayersType;
use App\Repository\PayersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin/payers')]
class AdminPayersController extends AbstractController
{
    #[Route('/', name: 'app_admin_payers_index', methods: ['GET'])]
    public function index(PayersRepository $payersRepository): Response
    {
        return $this->render('admin_payers/index.html.twig', [
            'payers' => $payersRepository->findAll(),
            'loading' => false,
        ]);
    }

    #[Route('/new', name: 'app_admin_payers_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $payer = new Payers();
        $form = $this->createForm(PayersType::class, $payer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($payer);
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_payers_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_payers/new.html.twig', [
            'payer' => $payer,
            'form' => $form,
            'loading' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_payers_show', methods: ['GET'])]
    public function show(Payers $payer): Response
    {
        return $this->render('admin_payers/show.html.twig', [
            'payer' => $payer,
            'loading' => false,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_admin_payers_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Payers $payer, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PayersType::class, $payer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_admin_payers_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('admin_payers/edit.html.twig', [
            'payer' => $payer,
            'form' => $form,
            'loading' => false,
        ]);
    }

    #[Route('/{id}', name: 'app_admin_payers_delete', methods: ['POST'])]
    public function delete(Request $request, Payers $payer, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$payer->getId(), $request->getPayload()->get('_token'))) {
            $entityManager->remove($payer);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_admin_payers_index', [], Response::HTTP_SEE_OTHER);
    }
}
