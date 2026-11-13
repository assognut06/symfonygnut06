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
#[IsGranted('ROLE_ENTREPRISE')]
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

        $missions = $entityManager
            ->getRepository(Mission::class)
            ->findBy(
                ['compteEntreprise' => $compte],
                [
                    'createdAt' => 'DESC',
                    'id' => 'DESC',
                ]
            );

        return $this->render('entreprise_mission/index.html.twig', [
            'compte' => $compte,
            'missions' => $missions,
            'editableStatuses' => self::EDITABLE_STATUSES,
        ]);
    }

    #[Route('/nouvelle', name: 'new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $compte = $this->getCompteEntreprise($entityManager);

        $mission = new Mission($compte);

        $form = $this->createForm(MissionType::class, $mission);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submitForReview = $form
                ->get('submitForReview')
                ->isClicked();

            if ($submitForReview) {
                if (!$compte->isApproved()) {
                    $form->addError(new FormError(
                        'Votre compte entreprise doit être validé avant de soumettre une mission.'
                    ));
                } else {
                    $mission->submit();
                }
            }

            if ($form->isValid()) {
                $entityManager->persist($mission);
                $entityManager->flush();

                if ($submitForReview) {
                    $this->addFlash(
                        'success',
                        'Votre mission a été soumise à validation.'
                    );
                } else {
                    $this->addFlash(
                        'success',
                        'Votre brouillon a été enregistré.'
                    );
                }

                return $this->redirectToRoute(
                    'entreprise_mission_index'
                );
            }
        }

        return $this->render('entreprise_mission/form.html.twig', [
            'form' => $form->createView(),
            'pageTitle' => 'Créer une mission',
        ]);
    }

    #[Route(
        '/{id}/modifier',
        name: 'edit',
        requirements: ['id' => '\d+'],
        methods: ['GET', 'POST']
    )]
    public function edit(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $compte = $this->getCompteEntreprise($entityManager);

        $mission = $entityManager
            ->getRepository(Mission::class)
            ->findOneBy([
                'id' => $id,
                'compteEntreprise' => $compte,
            ]);

        if (!$mission instanceof Mission) {
            throw $this->createNotFoundException(
                'Mission introuvable.'
            );
        }

        $initialStatus = $mission->getStatut();

        if (!in_array(
            $initialStatus,
            self::EDITABLE_STATUSES,
            true
        )) {
            throw $this->createAccessDeniedException(
                'Cette mission ne peut pas être modifiée dans son état actuel.'
            );
        }

        $form = $this->createForm(MissionType::class, $mission);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $submitForReview = $form
                ->get('submitForReview')
                ->isClicked();

            if ($submitForReview) {
                if (!$compte->isApproved()) {
                    $form->addError(new FormError(
                        'Votre compte entreprise doit être validé avant toute soumission.'
                    ));
                } elseif (in_array($mission->getStatut(),[ Mission::STATUS_DRAFT,Mission::STATUS_CORRECTION],true)) {
                  
                    $mission->submit();
                
                    } elseif ($initialStatus === Mission::STATUS_PUBLISHED && $mission->getStatut() === Mission::STATUS_PENDING) {
                    
                $this->addFlash(
                        'info',
                        'Les modifications de cette mission nécessitent une nouvelle validation.'
                    );
                } else {
                    $form->addError(new FormError(
                        'Cette mission ne peut pas être soumise dans son état actuel.'
                    ));
                }
            }

            if ($form->isValid()) {
                $entityManager->flush();

                if ($submitForReview) {
                    $this->addFlash(
                        'success',
                        'Votre mission a été soumise à validation.'
                    );
                } elseif (
                    $initialStatus === Mission::STATUS_PUBLISHED
                    && $mission->getStatut() === Mission::STATUS_PENDING
                ) {
                    $this->addFlash(
                        'success',
                        'Les modifications ont été enregistrées. La mission est en attente de validation.'
                    );
                } else {
                    $this->addFlash(
                        'success',
                        'La mission a été mise à jour.'
                    );
                }

                return $this->redirectToRoute(
                    'entreprise_mission_index'
                );
            }
        }

        return $this->render('entreprise_mission/form.html.twig', [
            'form' => $form->createView(),
            'pageTitle' => 'Modifier une mission',
        ]);
    }

    private function getCompteEntreprise(
        EntityManagerInterface $entityManager
    ): CompteEntreprise {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'Vous devez être connecté pour accéder à cet espace.'
            );
        }

        $compte = $entityManager
            ->getRepository(CompteEntreprise::class)
            ->findOneBy([
                'user' => $user,
            ]);

        if (!$compte instanceof CompteEntreprise) {
            throw $this->createAccessDeniedException(
                'Aucun profil entreprise n’est associé à votre compte.'
            );
        }

        return $compte;
    }
}