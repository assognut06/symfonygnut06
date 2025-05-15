<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Service\PaginationService;

class AdminUserController extends AbstractController
{
    #[Route('/admin/user/{page}', name: 'app_admin_user', defaults: ['page' => 1])]
    public function index(PaginationService $paginationService, int $page = 1): Response
    {
        $pagination = $paginationService->getPaginatedData(User::class, $page);

        return $this->render('admin/admin_user/index.html.twig', [
            'users' => $pagination['data'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'page' => $pagination['current_page'],
        ]);
    }

    #[Route('/admin/user/promote/{id}', name: 'app_admin_user_promote', methods: ['POST'])]    
    public function promoteUser(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        // Vérifier le token CSRF
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('promote_user'.$id, $token)) {
            throw $this->createAccessDeniedException('Action non autorisée.');
        }
    
        $user = $entityManager->getRepository(User::class)->find($id);
        if (!$user) {
            throw $this->createNotFoundException('L\'utilisateur n\'a pas été trouvé.');
        }
    
        // Mettre à jour le rôle de l'utilisateur
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();
    
        // Rediriger vers la liste des utilisateurs après la mise à jour
        return $this->redirectToRoute('app_admin_user');
    }

    #[Route('/admin/user/delete/{id}', name: 'app_admin_user_delete', methods: ['POST', 'DELETE'])]
    public function deleteUser(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $method = $request->request->get('_method', 'POST');
        if ($method === 'DELETE') {
            $token = $request->request->get('_token');
            if ($this->isCsrfTokenValid('delete'.$id, $token)) {
                $user = $entityManager->getRepository(User::class)->find($id);
                if (!$user) {
                    throw $this->createNotFoundException('L\'utilisateur n\'a pas été trouvé.');
                }
    
                // Supprimer les demandes de réinitialisation de mot de passe liées
                $resetPasswordRequests = $entityManager->getRepository(ResetPasswordRequest::class)->findBy(['user' => $id]);
                foreach ($resetPasswordRequests as $request) {
                    $entityManager->remove($request);
                }
                $entityManager->flush(); // Appliquer la suppression des demandes
                
                // Ensuite, supprimer l'utilisateur comme avant
                $entityManager->remove($user);
                $entityManager->flush();
    
                $this->addFlash('success', 'Utilisateur supprimé avec succès.');
            } else {
                $this->addFlash('danger', 'Token de sécurité invalide.');
            }
        } else {
            $this->addFlash('danger', 'Méthode non autorisée pour cette action.');
        }
    
        return $this->redirectToRoute('app_admin_user');
    }
}
