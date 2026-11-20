<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminUserController extends AbstractController
{
    private const PAGE_SIZE = 12;

    #[Route('/admin/user/{page}', name: 'app_admin_user', defaults: ['page' => 1])]
    public function index(
        Request $request,
        EntityManagerInterface $entityManager,
        int $page = 1
    ): Response {
        $query = trim((string) $request->query->get('q', ''));
        $page = max(1, $page);

        $qb = $entityManager
            ->getRepository(User::class)
            ->createQueryBuilder('u')
            ->orderBy('u.id', 'DESC');

        if ($query !== '') {
            $qb->andWhere('LOWER(u.email) LIKE :query')
                ->setParameter('query', '%' . mb_strtolower($query) . '%');
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


    #[Route('/admin/user/promote/{id}',name: 'app_admin_user_promote',methods: ['POST'])]
    public function promoteUser(
        Request $request,
        EntityManagerInterface $entityManager,
        int $id
    ): Response {
        $token = $request->request->getString('_token');
    
        if (!$this->isCsrfTokenValid('promote_user' . $id, $token)) {
            throw $this->createAccessDeniedException(
                'Action non autorisée.'
            );
        }
    
        $user = $entityManager
            ->getRepository(User::class)
            ->find($id);
    
        if (!$user instanceof User) {
            throw $this->createNotFoundException(
                'Utilisateur introuvable.'
            );
        }
    
        if (!$user->isActive()) {
            $this->addFlash(
                'warning',
                'Un compte inactif ne peut pas être promu ou rétrogradé.'
            );
    
            return $this->redirectToRoute('app_admin_user');
        }
    
        $roles = $user->getRoles();
    
        if (in_array('ROLE_ADMIN', $roles, true)) {
            $currentUser = $this->getUser();
    
            if (
                $currentUser instanceof User
                && $currentUser->getId() === $user->getId()
            ) {
                $this->addFlash(
                    'warning',
                    'Vous ne pouvez pas retirer votre propre rôle administrateur.'
                );
    
                return $this->redirectToRoute('app_admin_user');
            }
    
            $roles = array_values(
                array_filter(
                    $roles,
                    static fn (string $role): bool =>
                        $role !== 'ROLE_ADMIN'
                )
            );
    
            $this->addFlash(
                'info',
                'Utilisateur rétrogradé.'
            );
        } else {
            $roles[] = 'ROLE_ADMIN';
    
            $this->addFlash(
                'success',
                'Utilisateur promu.'
            );
        }
    
        $user->setRoles($roles);
    
        $entityManager->flush();
    
        return $this->redirectToRoute('app_admin_user');
    }


    #[Route(
        '/admin/user/delete/{id}',
        name: 'app_admin_user_delete',
        methods: ['POST', 'DELETE']
    )]
    public function deleteUser(
        Request $request,
        EntityManagerInterface $entityManager,
        int $id
    ): Response {
        $method = $request->request->get('_method', 'POST');

        if ($method !== 'DELETE' && !$request->isMethod('DELETE')) {
            $this->addFlash(
                'danger',
                'Méthode non autorisée pour cette action.'
            );

            return $this->redirectToRoute('app_admin_user');
        }

        $token = $request->request->get('_token');

        if (!$this->isCsrfTokenValid('delete' . $id, $token)) {
            $this->addFlash(
                'danger',
                'Token de sécurité invalide.'
            );

            return $this->redirectToRoute('app_admin_user');
        }

        $user = $entityManager
            ->getRepository(User::class)
            ->find($id);

        if (!$user instanceof User) {
            throw $this->createNotFoundException(
                "L'utilisateur n'a pas été trouvé."
            );
        }

        $currentUser = $this->getUser();

        if (
            $currentUser instanceof User
            && $currentUser->getId() === $user->getId()
        ) {
            $this->addFlash(
                'warning',
                'Vous ne pouvez pas désactiver votre propre compte.'
            );

            return $this->redirectToRoute('app_admin_user');
        }

        if (!$user->isActive()) {
            $this->addFlash(
                'info',
                'Cet utilisateur est déjà désactivé.'
            );

            return $this->redirectToRoute('app_admin_user');
        }

        $user->setActive(false);

        $entityManager->flush();

        $this->addFlash(
            'success',
            'Le compte utilisateur a été désactivé avec succès.'
        );

        return $this->redirectToRoute('app_admin_user');
    }

    #[Route(
        '/admin/user/reactivate/{id}',
        name: 'app_admin_user_reactivate',
        methods: ['POST']
    )]
    public function reactivateUser(
        Request $request,
        EntityManagerInterface $entityManager,
        int $id
    ): Response {
        $token = $request->request->getString('_token');
    
        if (!$this->isCsrfTokenValid('reactivate' . $id, $token)) {
            throw $this->createAccessDeniedException(
                'Action non autorisée.'
            );
        }
    
        $user = $entityManager
            ->getRepository(User::class)
            ->find($id);
    
        if (!$user instanceof User) {
            throw $this->createNotFoundException(
                'Utilisateur introuvable.'
            );
        }
    
        if ($user->isActive()) {
            $this->addFlash(
                'info',
                'Ce compte est déjà actif.'
            );
    
            return $this->redirectToRoute('app_admin_user');
        }
    
        $user->setActive(true);
    
        $entityManager->flush();
    
        $this->addFlash(
            'success',
            'Le compte utilisateur a été réactivé.'
        );
    
        return $this->redirectToRoute('app_admin_user');
    }
}