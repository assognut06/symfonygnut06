<?php

namespace App\Controller;

use App\Entity\CompteEntreprise;
use App\Entity\User;
use App\Form\CompanyRegistrationType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/entreprise/compte', name: 'company_registration_')]
#[IsGranted('ROLE_USER')]

#[Route('/statut', name: 'status', methods: ['GET'])]
final class CompanyRegistrationController extends AbstractController
{
    #[Route('/inscription', name: 'new', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'Vous devez être connecté pour créer un compte entreprise.'
            );
        }

        $existingAccount = $entityManager
            ->getRepository(CompteEntreprise::class)
            ->findOneBy([
                'user' => $user,
            ]);

        if ($existingAccount instanceof CompteEntreprise) {
            if ($this->assignEnterpriseRole($user)) {
                $entityManager->flush();
            }

            return $this->redirectToRoute(
                'company_registration_status'
            );
        }

        $compteEntreprise = new CompteEntreprise($user);

        $form = $this->createForm(
            CompanyRegistrationType::class,
            $compteEntreprise
        );

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($compteEntreprise);

            $this->assignEnterpriseRole($user);

            $entityManager->flush();

            $this->addFlash(
                'success',
                'Votre demande de création de compte entreprise a été enregistrée avec succès.'
            );

            return $this->redirectToRoute(
                'company_registration_status'
            );
        }

        return $this->render(
            'company_registration/form.html.twig',
            [
                'form' => $form->createView(),
                'pageTitle' => 'Créer un compte entreprise',
            ]
        );
    }

    #[Route('/statut', name: 'status', methods: ['GET'])]
    public function status(
        EntityManagerInterface $entityManager
    ): Response {
        $user = $this->getUser();

        if (!$user instanceof User) {
            throw $this->createAccessDeniedException(
                'Vous devez être connecté pour consulter votre compte entreprise.'
            );
        }

        $compteEntreprise = $entityManager
            ->getRepository(CompteEntreprise::class)
            ->findOneBy([
                'user' => $user,
            ]);

        if (!$compteEntreprise instanceof CompteEntreprise) {
            return $this->redirectToRoute(
                'company_registration_new'
            );
        }

        $statusLabels = [
            CompteEntreprise::STATUS_PENDING => 'En attente de validation',
            CompteEntreprise::STATUS_APPROVED => 'Compte approuvé',
            CompteEntreprise::STATUS_REJECTED => 'Demande refusée',
            CompteEntreprise::STATUS_SUSPENDED => 'Compte suspendu',
        ];

        $statusDescriptions = [
            CompteEntreprise::STATUS_PENDING =>
                'Votre demande a été enregistrée. Elle est en attente de vérification par l’équipe GNUT06.',

            CompteEntreprise::STATUS_APPROVED =>
                'Votre compte entreprise a été validé. Vous pouvez désormais soumettre vos missions à validation.',

            CompteEntreprise::STATUS_REJECTED =>
                'Votre demande de création de compte entreprise a été refusée. Contactez l’équipe GNUT06 pour obtenir des informations.',

            CompteEntreprise::STATUS_SUSPENDED =>
                'Votre compte entreprise est actuellement suspendu. Contactez l’équipe GNUT06 pour obtenir des informations.',
        ];

        $status = $compteEntreprise->getStatut();

        return $this->render(
            'company_registration/status.html.twig',
            [
                'compte' => $compteEntreprise,
                'status' => $status,
                'statusLabel' => $statusLabels[$status] ?? 'Statut inconnu',
                'statusDescription' => $statusDescriptions[$status] ?? '',
                'isApproved' => $compteEntreprise->isApproved(),
            ]
        );
    }

    private function assignEnterpriseRole(User $user): bool
    {
        $roles = $user->getRoles();

        if (in_array('ROLE_ENTREPRISE', $roles, true)) {
            return false;
        }

        $roles[] = 'ROLE_ENTREPRISE';

        $user->setRoles($roles);

        return true;
    }
}