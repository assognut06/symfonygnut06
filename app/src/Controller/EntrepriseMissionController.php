<?php

namespace App\Controller;

use App\Entity\CompteEntreprise;
use App\Entity\Mission;
use App\Entity\User;
use App\Form\MissionType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/entreprise/missions', name: 'entreprise_mission_')]
#[IsGranted('ROLE_USER')]
final class EntrepriseMissionController extends AbstractController
{
    private const EDITABLE_STATUSES = [
        Mission::STATUS_DRAFT,
        Mission::STATUS_CORRECTION,
        Mission::STATUS_PUBLISHED,
    ];

    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $compte = $this->getCompteEntreprise($entityManager);
        $missions = $entityManager->getRepository(Mission::class)->findBy(
            ['compteEntreprise' => $compte],
            ['createdAt' => 'DESC', 'id' => 'DESC']
        );

        return $this->render('entreprise_mission/index.html.twig', [
            'compte' => $compte,
            'missions' => $missions,
            'editableStatuses' => self::EDITABLE_STATUSES,
        ]);
    }

    #[Route('/nouvelle', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $compte = $this->getCompteEntreprise($entityManager);
        $mission = new Mission($compte);
        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('submitForReview')->isClicked()) {
                if (!$compte->isApproved()) {
                    $form->addError(new FormError('Votre compte entreprise doit être validé avant toute soumission.'));
                } else {
                    $mission->submit();
                }
            }

            if ($form->isValid()) {
                $entityManager->persist($mission);
                $entityManager->flush();
                $this->addFlash('success', 'La mission a été enregistrée.');

                return $this->redirectToRoute('entreprise_mission_index');
            }
        }

        return $this->render('entreprise_mission/form.html.twig', [
            'form' => $form->createView(),
            'pageTitle' => 'Créer une mission',
        ]);
    }

    #[Route('/{id}/modifier', name: 'edit', requirements: ['id' => '\\d+'], methods: ['GET', 'POST'])]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $compte = $this->getCompteEntreprise($entityManager);
        $mission = $entityManager->getRepository(Mission::class)->findOneBy([
            'id' => $id,
            'compteEntreprise' => $compte,
        ]);

        if (!$mission instanceof Mission) {
            throw $this->createNotFoundException('Mission introuvable.');
        }

        if (!in_array($mission->getStatut(), self::EDITABLE_STATUSES, true)) {
            throw $this->createAccessDeniedException('Cette mission ne peut pas être modifiée dans son état actuel.');
        }

        $form = $this->createForm(MissionType::class, $mission);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if ($form->get('submitForReview')->isClicked()) {
                if (!$compte->isApproved()) {
                    $form->addError(new FormError('Votre compte entreprise doit être validé avant toute soumission.'));
                } elseif (in_array($mission->getStatut(), [Mission::STATUS_DRAFT, Mission::STATUS_CORRECTION], true)) {
                    $mission->submit();
                } elseif ($mission->getStatut() !== Mission::STATUS_PENDING) {
                    $form->addError(new FormError('Cette mission est déjà publiée et aucune nouvelle modification ne nécessite de validation.'));
                }
            }

            if ($form->isValid()) {
                $entityManager->flush();
                $this->addFlash('success', 'La mission a été mise à jour.');

                return $this->redirectToRoute('entreprise_mission_index');
            }
        }

        return $this->render('entreprise_mission/form.html.twig', [
            'form' => $form->createView(),
            'pageTitle' => 'Modifier une mission',
        ]);
    }

    private function getCompteEntreprise(EntityManagerInterface $entityManager): CompteEntreprise
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException('Connexion requise.');
        }

        $compte = $entityManager->getRepository(CompteEntreprise::class)->findOneBy(['user' => $user]);

        if (!$compte instanceof CompteEntreprise) {
            throw $this->createAccessDeniedException('Aucun profil entreprise n’est associé à votre compte.');
        }

        return $compte;
    }
}
