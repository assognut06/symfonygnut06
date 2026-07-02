<?php

namespace App\Controller;

use App\Entity\Tih;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
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

            // Recherche sur toute la table TIH et sur l'email du compte utilisateur lie.
            $qb->andWhere('LOWER(u.email) LIKE :q 
                           OR LOWER(COALESCE(t.professionalEmail, \'\')) LIKE :q
                           OR LOWER(COALESCE(t.lastName, \'\')) LIKE :q
                           OR LOWER(COALESCE(t.firstName, \'\')) LIKE :q')
               ->setParameter('q', $needle);
        }

        $total = (int) (clone $qb)
            ->select('COUNT(t.id)')
            ->getQuery()
            ->getSingleScalarResult();

        $pages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $page = min($page, $pages);

        $tihs = $qb
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getResult();

        return $this->render('admin/admin_tih/index.html.twig', [
            'tihs'      => $tihs,
            'page'      => $page,
            'pages'     => $pages,
            'total'     => $total,
            'page_size' => self::PAGE_SIZE,
            'query'     => $q,
        ]);
    }

    #[Route('/admin/tih/{id}/cv', name: 'app_admin_tih_cv', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadCv(EntityManagerInterface $em, int $id): Response
    {
        $tih = $this->findTihOrFail($em, $id);

        if (!$tih->getCv()) {
            throw $this->createNotFoundException('CV introuvable.');
        }

        return $this->createInlineFileResponse(
            (string) $tih->getCv(),
            (string) $this->getParameter('cv_tih_directory'),
            'public/uploads/tihcv',
            'CV introuvable.'
        );
    }

    #[Route('/admin/tih/{id}/attestation', name: 'app_admin_tih_attestation', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadAttestation(EntityManagerInterface $em, int $id): Response
    {
        $tih = $this->findTihOrFail($em, $id);

        if (!$tih->getAttestationTih()) {
            throw $this->createNotFoundException('Attestation introuvable.');
        }

        return $this->createInlineFileResponse(
            (string) $tih->getAttestationTih(),
            (string) $this->getParameter('attestation_tih_directory'),
            'public/uploads/tihattest',
            'Attestation introuvable.'
        );
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

    private function findTihOrFail(EntityManagerInterface $em, int $id): Tih
    {
        $tih = $em->getRepository(Tih::class)->find($id);

        if (!$tih) {
            throw $this->createNotFoundException('Le TIH n\'a pas été trouvé.');
        }

        return $tih;
    }

    private function createInlineFileResponse(
        string $storedFilename,
        string $uploadDirectory,
        string $legacyDirectory,
        string $notFoundMessage
    ): BinaryFileResponse {
        $fileName = basename($storedFilename);
        $filePath = rtrim($uploadDirectory, '/') . '/' . $fileName;

        if (!is_file($filePath)) {
            $legacyPath = rtrim((string) $this->getParameter('kernel.project_dir'), '/') . '/' . trim($legacyDirectory, '/') . '/' . $fileName;

            if (is_file($legacyPath)) {
                $filePath = $legacyPath;
            } else {
                throw $this->createNotFoundException($notFoundMessage);
            }
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);

        return $response;
    }
}
