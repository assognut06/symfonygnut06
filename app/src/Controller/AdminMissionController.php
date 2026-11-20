<?php

namespace App\Controller;

use App\Entity\DecisionModeration;
use App\Entity\Mission;
use App\Entity\User;
use App\Service\MissionModerationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/missions', name: 'admin_mission_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminMissionController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $missionRepository = $entityManager->getRepository(Mission::class);

        $missions = $missionRepository->findBy([], [
            'createdAt' => 'DESC',
            'id' => 'DESC',
        ]);

        $pendingCount = $missionRepository->count([
            'statut' => Mission::STATUS_PENDING,
        ]);

        $publishedCount = $missionRepository->count([
            'statut' => Mission::STATUS_PUBLISHED,
        ]);

        $correctionCount = $missionRepository->count([
            'statut' => Mission::STATUS_CORRECTION,
        ]);

        return $this->render('admin_mission/index.html.twig', [
            'missions' => $missions,
            'pendingCount' => $pendingCount,
            'publishedCount' => $publishedCount,
            'correctionCount' => $correctionCount,
            'statusLabels' => $this->getStatusLabels(),
        ]);
    }

    #[Route(
        '/{id}',
        name: 'show',
        requirements: ['id' => '\d+'],
        methods: ['GET']
    )]
    public function show(
        int $id,
        EntityManagerInterface $entityManager,
        MissionModerationService $moderationService
    ): Response {
        $mission = $this->getMission($entityManager, $id);

        $decisions = $entityManager
            ->getRepository(DecisionModeration::class)
            ->findBy(
                ['mission' => $mission],
                ['createdAt' => 'DESC', 'id' => 'DESC']
            );

        return $this->render('admin_mission/show.html.twig', [
            'mission' => $mission,
            'decisions' => $decisions,
            'canPublish' => $moderationService->canPublish($mission),
            'statusLabels' => $this->getStatusLabels(),
        ]);
    }

    #[Route(
        '/{id}/publier',
        name: 'publish',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function publish(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        MissionModerationService $moderationService
    ): Response {
        $mission = $this->getMission($entityManager, $id);

        $this->checkCsrfToken($request, 'publish', $id);

        $moderator = $this->getModerator();

        try {
            $moderationService->publish($mission, $moderator);

            $this->addFlash(
                'success',
                'La mission a été publiée avec succès.'
            );
        } catch (\DomainException $exception) {
            $this->addFlash(
                'warning',
                $exception->getMessage()
            );
        }

        return $this->redirectToRoute('admin_mission_show', [
            'id' => $id,
        ]);
    }

    #[Route(
        '/{id}/correction',
        name: 'correction',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function correction(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        MissionModerationService $moderationService
    ): Response {
        $mission = $this->getMission($entityManager, $id);

        $this->checkCsrfToken($request, 'correction', $id);

        $reason = $request->request->getString('reason');

        $moderator = $this->getModerator();

        try {
            $moderationService->requestCorrection(
                $mission,
                $moderator,
                $reason
            );

            $this->addFlash(
                'success',
                'La demande de correction a été enregistrée.'
            );
        } catch (\DomainException $exception) {
            $this->addFlash(
                'warning',
                $exception->getMessage()
            );
        }

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
        EntityManagerInterface $entityManager,
        MissionModerationService $moderationService
    ): Response {
        $mission = $this->getMission($entityManager, $id);

        $this->checkCsrfToken($request, 'withdraw', $id);

        $reason = $request->request->getString('reason');

        $moderator = $this->getModerator();

        try {
            $moderationService->withdraw(
                $mission,
                $moderator,
                $reason
            );

            $this->addFlash(
                'success',
                'La mission a été retirée de la publication.'
            );
        } catch (\DomainException $exception) {
            $this->addFlash(
                'warning',
                $exception->getMessage()
            );
        }

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
            Mission::STATUS_DRAFT => 'Brouillon',
            Mission::STATUS_PENDING => 'En attente de validation',
            Mission::STATUS_CORRECTION => 'À corriger',
            Mission::STATUS_PUBLISHED => 'Publiée',
            Mission::STATUS_CLOSED => 'Clôturée',
            Mission::STATUS_EXPIRED => 'Expirée',
            Mission::STATUS_WITHDRAWN => 'Retirée',
        ];
    }
}