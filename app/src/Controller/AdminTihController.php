<?php

namespace App\Controller;

use App\Entity\Tih;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminTihController extends AbstractController
{
    private const PAGE_SIZE = 10;

    #[Route('/admin/tih/{page}', name: 'app_admin_tih', defaults: ['page' => 1], methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, int $page = 1): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        $qb = $em->getRepository(Tih::class)->createQueryBuilder('t')
            ->leftJoin('t.user', 'u')->addSelect('u')
            ->orderBy('t.id', 'DESC');

        if ($q !== '') {
            $needle = '%'.mb_strtolower($q).'%';

            // Recherche sur email (utilisateur), nom et prénom (entité TIH)
            $qb->andWhere('LOWER(u.email) LIKE :q 
                           OR LOWER(COALESCE(t.nom, \'\')) LIKE :q
                           OR LOWER(COALESCE(t.prenom, \'\')) LIKE :q')
               ->setParameter('q', $needle);
        }

        $query = $qb
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery();

        $paginator = new Paginator($query, true);
        $total = count($paginator);
        $pages = (int) ceil($total / self::PAGE_SIZE);

        return $this->render('admin/admin_tih/index.html.twig', [
            'tihs'      => iterator_to_array($paginator),
            'page'      => $page,
            'pages'     => $pages,
            'total'     => $total,
            'page_size' => self::PAGE_SIZE,
            'query'     => $q,
        ]);
    }

    #[Route('/admin/tih/validate/{id}', name: 'app_admin_tih_validate', methods: ['POST'])]
    public function validate(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('validate_tih'.$id, $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_tih');
        }

        $tih = $em->getRepository(Tih::class)->find($id);
        if (!$tih) {
            throw $this->createNotFoundException('Le TIH n\'a pas été trouvé.');
        }

        $tih->setIsValidate(true);
        $tih->setValidationMessage(null);
        $em->flush();

        $this->addFlash('success', 'Profil TIH validé avec succès.');
        return $this->redirectToRoute('app_admin_tih');
    }

    #[Route('/admin/tih/refuse/{id}', name: 'app_admin_tih_refuse', methods: ['POST'])]
    public function refuse(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('refuse_tih'.$id, $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_tih');
        }

        $tih = $em->getRepository(Tih::class)->find($id);
        if (!$tih) {
            throw $this->createNotFoundException('Le TIH n\'a pas été trouvé.');
        }

        // Message personnalisé saisi dans le modal
        $message = trim((string) $request->request->get('validation_message', ''));
        if ($message === '') {
            $message = 'Vos informations ne sont pas correctes.';
        }

        $tih->setIsValidate(false);
        $tih->setValidationMessage($message);
        $em->flush();

        $this->addFlash('success', 'Le profil TIH a été refusé avec un message personnalisé.');
        return $this->redirectToRoute('app_admin_tih');
    }

    #[Route('/admin/tih/delete/{id}', name: 'app_admin_tih_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $method = $request->request->get('_method', 'POST');

        if ($method !== 'DELETE') {
            $this->addFlash('danger', 'Méthode non autorisée pour cette action.');
            return $this->redirectToRoute('app_admin_tih');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_tih'.$id, $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_tih');
        }

        $tih = $em->getRepository(Tih::class)->find($id);
        if (!$tih) {
            throw $this->createNotFoundException('Le TIH n\'a pas été trouvé.');
        }

        $em->remove($tih);
        $em->flush();

        $this->addFlash('success', 'TIH supprimé avec succès.');
        return $this->redirectToRoute('app_admin_tih');
    }
}
