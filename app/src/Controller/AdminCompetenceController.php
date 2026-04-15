<?php

namespace App\Controller;

use App\Entity\Competence;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class AdminCompetenceController extends AbstractController
{
    private const PAGE_SIZE = 10;

    #[Route('/admin/competence/{page}', name: 'app_admin_competence', defaults: ['page' => 1], methods: ['GET'])]
    public function index(Request $request, EntityManagerInterface $em, int $page = 1): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        $qb = $em->getRepository(Competence::class)->createQueryBuilder('c')
            ->orderBy('c.id', 'DESC');

        if ($q !== '') {
            $qb->andWhere('LOWER(c.name) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $query = $qb
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery();

        $paginator = new Paginator($query, true);
        $total = count($paginator);
        $pages = (int) ceil($total / self::PAGE_SIZE);

        return $this->render('admin/admin_competence/index.html.twig', [
            'competences' => iterator_to_array($paginator),
            'page'        => $page,
            'pages'       => $pages,
            'total'       => $total,
            'query'       => $q,
        ]);
    }

    #[Route('/admin/competence/add', name: 'app_admin_competence_add', methods: ['POST'])]
    public function add(Request $request, EntityManagerInterface $em): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('add_competence', $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_competence');
        }

        $name = trim((string) $request->request->get('name', ''));
        if ($name === '') {
            $this->addFlash('warning', 'Le nom de la compétence est requis.');
            return $this->redirectToRoute('app_admin_competence');
        }

        // (Optionnel) éviter les doublons simples
        $existing = $em->getRepository(Competence::class)->findOneBy(['name' => $name]);
        if ($existing) {
            $this->addFlash('info', 'Cette compétence existe déjà.');
            return $this->redirectToRoute('app_admin_competence');
        }

        $competence = (new Competence())->setName($name);
        $em->persist($competence);
        $em->flush();

        $this->addFlash('success', 'Compétence ajoutée avec succès.');
        return $this->redirectToRoute('app_admin_competence');
    }

    #[Route('/admin/competence/delete/{id}', name: 'app_admin_competence_delete', methods: ['POST', 'DELETE'])]
    public function delete(Request $request, EntityManagerInterface $em, int $id): Response
    {
        $method = $request->request->get('_method', 'POST');
        if ($method !== 'DELETE') {
            $this->addFlash('danger', 'Méthode non autorisée pour cette action.');
            return $this->redirectToRoute('app_admin_competence');
        }

        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('delete_competence'.$id, $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_competence');
        }

        $competence = $em->getRepository(Competence::class)->find($id);
        if (!$competence) {
            throw $this->createNotFoundException('La compétence n’a pas été trouvée.');
        }

        $em->remove($competence);
        $em->flush();

        $this->addFlash('success', 'Compétence supprimée avec succès.');
        return $this->redirectToRoute('app_admin_competence');
    }
}
