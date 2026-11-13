<?php

namespace App\Controller;

use App\Entity\CompteEntreprise;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/comptes-entreprises', name: 'admin_company_')]
#[IsGranted('ROLE_ADMIN')]
final class AdminCompanyController extends AbstractController
{
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager): Response
    {
        $accounts = $entityManager
            ->getRepository(CompteEntreprise::class)
            ->findBy([], [
                'createdAt' => 'DESC',
                'id' => 'DESC',
            ]);

        $statusLabels = [
            CompteEntreprise::STATUS_PENDING => 'En attente',
            CompteEntreprise::STATUS_APPROVED => 'Approuvé',
            CompteEntreprise::STATUS_REJECTED => 'Refusé',
            CompteEntreprise::STATUS_SUSPENDED => 'Suspendu',
        ];

        return $this->render('admin_company/index.html.twig', [
            'accounts' => $accounts,
            'statusLabels' => $statusLabels,
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
        EntityManagerInterface $entityManager
    ): Response {
        $account = $this->getCompanyAccount($entityManager, $id);

        $statusLabels = [
            CompteEntreprise::STATUS_PENDING => 'En attente de validation',
            CompteEntreprise::STATUS_APPROVED => 'Compte approuvé',
            CompteEntreprise::STATUS_REJECTED => 'Demande refusée',
            CompteEntreprise::STATUS_SUSPENDED => 'Compte suspendu',
        ];

        return $this->render('admin_company/show.html.twig', [
            'account' => $account,
            'statusLabel' => $statusLabels[$account->getStatut()] ?? 'Statut inconnu',
        ]);
    }

    #[Route(
        '/{id}/approuver',
        name: 'approve',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function approve(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $account = $this->getCompanyAccount($entityManager, $id);

        $this->checkCsrfToken($request, 'approve', $id);

        if (!in_array(
            $account->getStatut(),
            [
                CompteEntreprise::STATUS_PENDING,
                CompteEntreprise::STATUS_SUSPENDED,
            ],
            true
        )) {
            $this->addFlash(
                'warning',
                'Ce compte ne peut pas être approuvé dans son état actuel.'
            );

            return $this->redirectToRoute('admin_company_show', [
                'id' => $id,
            ]);
        }

        $account->approve();

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le compte entreprise a été approuvé avec succès.'
        );

        return $this->redirectToRoute('admin_company_show', [
            'id' => $id,
        ]);
    }

    #[Route(
        '/{id}/refuser',
        name: 'reject',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function reject(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $account = $this->getCompanyAccount($entityManager, $id);

        $this->checkCsrfToken($request, 'reject', $id);

        if ($account->getStatut() !== CompteEntreprise::STATUS_PENDING) {
            $this->addFlash(
                'warning',
                'Seule une demande en attente peut être refusée.'
            );

            return $this->redirectToRoute('admin_company_show', [
                'id' => $id,
            ]);
        }

        $account->reject();

        $entityManager->flush();

        $this->addFlash(
            'success',
            'La demande de compte entreprise a été refusée.'
        );

        return $this->redirectToRoute('admin_company_show', [
            'id' => $id,
        ]);
    }

    #[Route(
        '/{id}/suspendre',
        name: 'suspend',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function suspend(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $account = $this->getCompanyAccount($entityManager, $id);

        $this->checkCsrfToken($request, 'suspend', $id);

        if ($account->getStatut() !== CompteEntreprise::STATUS_APPROVED) {
            $this->addFlash(
                'warning',
                'Seul un compte approuvé peut être suspendu.'
            );

            return $this->redirectToRoute('admin_company_show', [
                'id' => $id,
            ]);
        }

        $account->suspend();

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le compte entreprise a été suspendu.'
        );

        return $this->redirectToRoute('admin_company_show', [
            'id' => $id,
        ]);
    }

    #[Route(
        '/{id}/reexaminer',
        name: 'reopen',
        requirements: ['id' => '\d+'],
        methods: ['POST']
    )]
    public function reopen(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $account = $this->getCompanyAccount($entityManager, $id);

        $this->checkCsrfToken($request, 'reopen', $id);

        if ($account->getStatut() !== CompteEntreprise::STATUS_REJECTED) {
            $this->addFlash(
                'warning',
                'Seule une demande refusée peut être remise en examen.'
            );

            return $this->redirectToRoute('admin_company_show', [
                'id' => $id,
            ]);
        }

        $account->requestNewReview();

        $entityManager->flush();

        $this->addFlash(
            'success',
            'La demande a été remise en attente de validation.'
        );

        return $this->redirectToRoute('admin_company_show', [
            'id' => $id,
        ]);
    }

    private function getCompanyAccount(
        EntityManagerInterface $entityManager,
        int $id
    ): CompteEntreprise {
        $account = $entityManager
            ->getRepository(CompteEntreprise::class)
            ->find($id);

        if (!$account instanceof CompteEntreprise) {
            throw $this->createNotFoundException(
                'Compte entreprise introuvable.'
            );
        }

        return $account;
    }

    private function checkCsrfToken(
        Request $request,
        string $action,
        int $id
    ): void {
        $token = $request->request->getString('_token');

        if (!$this->isCsrfTokenValid(
            'admin_company_' . $action . '_' . $id,
            $token
        )) {
            throw $this->createAccessDeniedException(
                'Jeton de sécurité invalide.'
            );
        }
    }
}