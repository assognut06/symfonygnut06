<?php

namespace App\Controller;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
use App\Service\PaginationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
<<<<<<< HEAD
=======
use Doctrine\ORM\EntityManagerInterface;
>>>>>>> 88bde31 (Rebase from develop)
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    private const PAGE_SIZE = 12;

    #[Route('/admin/user/{page}', name: 'app_admin_user', defaults: ['page' => 1])]
    public function index(Request $request, EntityManagerInterface $entityManager, int $page = 1): Response
    {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, $page);

        $qb = $entityManager->getRepository(User::class)->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($query !== '') {
            $qb->andWhere('LOWER(u.email) LIKE :query')
                ->setParameter('query', '%'.mb_strtolower($query).'%');
        }

        $total = (int) (clone $qb)
            ->select('COUNT(u.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min($page, $pages);

        $users = $qb
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getResult();

        return $this->render('admin/admin_user/index.html.twig', [
            'users' => $users,
            'total' => $total,
            'pages' => $pages,
            'page' => $page,
            'query' => $query,
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
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            // Rétrograder → ne laisser que ROLE_USER
            $user->setRoles([]);
            $this->addFlash('info', 'Utilisateur rétrogradé.');
        } else {
            // Promouvoir
            $user->setRoles(['ROLE_ADMIN']);
            $this->addFlash('success', 'Utilisateur promu.');
        }

        $entityManager->flush();

        // Rediriger vers la liste des utilisateurs après la mise à jour
        return $this->redirectToRoute('app_admin_user');
    }

    #[Route('/admin/user/delete/{id}', name: 'app_admin_user_delete', methods: ['POST', 'DELETE'])]
    public function deleteUser(Request $request, EntityManagerInterface $entityManager, int $id): Response
    {
        $method = $request->request->get('_method', 'POST');
        if ('DELETE' === $method) {
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
