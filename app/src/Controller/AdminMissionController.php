<?php

namespace App\Controller;

use App\Entity\DecisionModeration;
use App\Entity\Mission;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/missions', name: 'admin_mission_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminMissionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $missions = $entityManager
            ->getRepository(Mission::class)
            ->findBy([], [
                'createdAt' => 'DESC',
                'id' => 'DESC',
            ]);

        $pendingCount = 0;
        $publishedCount = 0;
        $correctionCount = 0;

        foreach ($missions as $mission) {
            if ($mission->getStatut() === Mission::STATUS_PENDING) {
                $pendingCount++;
            }

            if ($mission->getStatut() === Mission::STATUS_PUBLISHED) {
                $publishedCount++;
            }

            if ($mission->getStatut() === Mission::STATUS_CORRECTION) {
                $correctionCount++;
            }
        }

        return $this->render('admin_mission/index.html.twig', [
            'missions' => $missions,
            'pendingCount' => $pendingCount,
            'publishedCount' => $publishedCount,
            'correctionCount' => $correctionCount,
            'statusLabels' => $this->getStatusLabels(),
        ]);
    }

    #[Route('/{id}',name: 'show',requirements: ['id' => '\d+'],methods: ['GET'])]
    public function show(int $id,EntityManagerInterface $entityManager): Response {

        $mission = $this->getMission($entityManager, $id);

        $decisions = $entityManager
            ->getRepository(DecisionModeration::class)
            ->findBy(
                ['mission' => $mission],
                ['createdAt' => 'DESC', 'id' => 'DESC']
            );

        $deadline = $mission->getDateLimiteReponse();

        $canPublish = $mission->getStatut() === Mission::STATUS_PENDING
            && $mission->getCompteEntreprise()->isApproved()
            && $deadline !== null
            && $deadline->format('Y-m-d')
                > (new \DateTimeImmutable('today'))->format('Y-m-d');

        return $this->render('admin_mission/show.html.twig', [
            'mission' => $mission,
            'decisions' => $decisions,
            'canPublish' => $canPublish,
            'statusLabels' => $this->getStatusLabels(),
        ]);
    }

    #[Route('/{id}/publier',name: 'publish',requirements: ['id' => '\d+'],methods: ['POST'])]
    public function publish(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        ValidatorInterface $validator
    ): Response {
        $mission = $this->getMission($entityManager, $id);

        $this->checkCsrfToken($request, 'publish', $id);

        if ($mission->getStatut() !== Mission::STATUS_PENDING) {
            $this->addFlash(
                'warning',
                'Seule une mission en attente peut être publiée.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        if (!$mission->getCompteEntreprise()->isApproved()) {
            $this->addFlash(
                'warning',
                'Le compte entreprise doit être approuvé avant la publication.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        $deadline = $mission->getDateLimiteReponse();

        if (
            $deadline === null
            || $deadline->format('Y-m-d')
                <= (new \DateTimeImmutable('today'))->format('Y-m-d')
        ) {
            $this->addFlash(
                'warning',
                'La date limite de réponse doit être future.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        $violations = $validator->validate(
            $mission,
            null,
            ['Default', 'mission_submit']
        );

        if (count($violations) > 0) {
            $this->addFlash(
                'warning',
                'La mission contient des informations manquantes ou invalides.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        $moderator = $this->getModerator();

        $mission->publish();

        $decision = new DecisionModeration(
            $mission,
            $moderator,
            DecisionModeration::DECISION_PUBLISH
        );

        $entityManager->persist($decision);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'La mission a été publiée avec succès.'
        );

        return $this->redirectToRoute('admin_mission_show', [
            'id' => $id,
        ]);
    }

    #[Route('/{id}/correction',name: 'correction',requirements: ['id' => '\d+'],methods: ['POST'])]
    public function correction(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $mission = $this->getMission($entityManager, $id);

        $this->checkCsrfToken($request, 'correction', $id);

        if ($mission->getStatut() !== Mission::STATUS_PENDING) {
            $this->addFlash(
                'warning',
                'Seule une mission en attente peut faire l’objet d’une demande de correction.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        $reason = trim($request->request->getString('reason'));

        if ($reason === '' || mb_strlen($reason) > 200) {
            $this->addFlash(
                'warning',
                'Le motif de correction est obligatoire et ne doit pas dépasser 200 caractères.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        $moderator = $this->getModerator();

        $mission->requestCorrection();

        $decision = new DecisionModeration(
            $mission,
            $moderator,
            DecisionModeration::DECISION_REQUEST_CORRECTION,
            $reason
        );

        $entityManager->persist($decision);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'La demande de correction a été enregistrée.'
        );

        return $this->redirectToRoute('admin_mission_show', [
            'id' => $id,
        ]);
    }

    #[Route(
        '/{id}/retirer',
        name: 'withdraw',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function withdraw(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $mission = $this->getMission($entityManager, $id);

        $this->checkCsrfToken($request, 'withdraw', $id);

        if ($mission->getStatut() !== Mission::STATUS_PUBLISHED) {
            $this->addFlash(
                'warning',
                'Seule une mission publiée peut être retirée.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        $reason = trim($request->request->getString('reason'));

        if ($reason === '' || mb_strlen($reason) > 2000) {
            $this->addFlash(
                'warning',
                'Le motif du retrait est obligatoire et ne doit pas dépasser 2 000 caractères.'
            );

            return $this->redirectToRoute('admin_mission_show', [
                'id' => $id,
            ]);
        }

        $moderator = $this->getModerator();

        $mission->withdraw();

        $decision = new DecisionModeration(
            $mission,
            $moderator,
            DecisionModeration::DECISION_WITHDRAW,
            $reason
        );

        $entityManager->persist($decision);
        $entityManager->flush();

        $this->addFlash(
            'success',
            'La mission a été retirée de la publication.'
        );

        return $this->redirectToRoute('admin_mission_show', [
            'id' => $id,
        ]);
    }

    private function getMission(
        EntityManagerInterface $entityManager,
        int $id
    ): Mission {
        $mission = $entityManager
            ->getRepository(Mission::class)
            ->find($id);

        if (!$mission instanceof Mission) {
            throw $this->createNotFoundException(
                'Mission introuvable.'
            );
        }

        return $mission;
    }

    private function getModerator(): User
    {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'Administrateur non identifié.'
            );
        }

        return $user;
    }

    private function checkCsrfToken(
        Request $request,
        string $action,
        int $id
    ): void {
        $token = $request->request->getString('_token');

        if (!$this->isCsrfTokenValid(
            'admin_mission_' . $action . '_' . $id,
            $token
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }
    }

    private function getStatusLabels(): array
    {
        return [
           // Mission::STATUS_DRAFT => 'Brouillon',
            Mission::STATUS_PENDING => 'En attente de validation',
            Mission::STATUS_CORRECTION => 'À corriger',
            Mission::STATUS_PUBLISHED => 'Publiée',
            Mission::STATUS_CLOSED => 'Clôturée',
            Mission::STATUS_EXPIRED => 'Expirée',
            Mission::STATUS_WITHDRAWN => 'Retirée',
        ];
    }
}