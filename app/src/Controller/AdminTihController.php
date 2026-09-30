<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Entity\TihApplicationEvent;
use App\Entity\User;
use App\Repository\TihRepository;
use App\Service\TihApplicationWorkflowService;
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
    public function index(Request $request, TihRepository $tihRepository, int $page = 1): Response
    {
        $q = trim((string) $request->query->get('q', ''));

        $qb = $tihRepository->createQueryBuilder('t')
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

        $pageRows = $qb
            ->select('t.id AS id')
            ->setFirstResult(($page - 1) * self::PAGE_SIZE)
            ->setMaxResults(self::PAGE_SIZE)
            ->getQuery()
            ->getScalarResult();
        $pageIds = array_map(static fn (array $row): int => (int) $row['id'], $pageRows);

        // Paginer les identifiants avant de charger les collections : un fetch join
        // sur les événements dans la requête paginée tronquerait les profils.
        $tihs = $tihRepository->findWithAdminDetailsByIds($pageIds);

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
    public function validate(Request $request, TihApplicationWorkflowService $workflow, int $id): Response
    {
        $token = $request->request->get('_token');
        if (!$this->isCsrfTokenValid('validate_tih'.$id, $token)) {
            $this->addFlash('danger', 'Token de sécurité invalide.');
            return $this->redirectToRoute('app_admin_tih');
        }

        $administrator = $this->getUser();
        if (!$administrator instanceof User) {
            throw $this->createAccessDeniedException();
        }

        $approved = $workflow->approve($id, $administrator);
        if (null === $approved) {
            throw $this->createNotFoundException('Le TIH n\'a pas été trouvé.');
        }

        if (!$approved) {
            $this->addFlash('warning', 'Cette candidature était déjà validée.');

            return $this->redirectToRoute('app_admin_tih');
        }

        $this->addFlash('success', 'Profil TIH validé avec succès.');
        return $this->redirectToRoute('app_admin_tih');
    }

    #[Route('/admin/tih/refuse/{id}', name: 'app_admin_tih_refuse', methods: ['POST'])]
    public function refuse(Request $request, TihApplicationWorkflowService $workflow, int $id): Response
    {
        return $this->submitReasonedDecision($request, $workflow, $id, false);
    }

    #[Route('/admin/tih/review/{id}', name: 'app_admin_tih_review', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function review(Request $request, TihApplicationWorkflowService $workflow, int $id): Response
    {
        return $this->submitReasonedDecision($request, $workflow, $id, true);
    }

    #[Route('/admin/tih/review/{id}/retry-email', name: 'app_admin_tih_retry_review_email', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retryReviewEmail(Request $request, TihApplicationWorkflowService $workflow, int $id): Response
    {
        return $this->retryDecisionEmail($request, $workflow, $id, true);
    }

    #[Route('/admin/tih/rejection/{id}/retry-email', name: 'app_admin_tih_retry_rejection_email', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function retryRejectionEmail(Request $request, TihApplicationWorkflowService $workflow, int $id): Response
    {
        return $this->retryDecisionEmail($request, $workflow, $id, false);
    }

    private function retryDecisionEmail(Request $request, TihApplicationWorkflowService $workflow, int $id, bool $reviewRequest): Response
    {
        $tokenId = ($reviewRequest ? 'retry_tih_review_email' : 'retry_tih_rejection_email').$id;
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_admin_tih');
        }

        $status = $reviewRequest ? $workflow->retryReviewRequestEmail($id) : $workflow->retryRejectionEmail($id);

        if ('not_found' === $status) {
            throw $this->createNotFoundException($reviewRequest
                ? 'La remise en attente n’a pas été trouvée.'
                : 'La décision de refus n’a pas été trouvée.');
        }

        if ('not_failed' === $status) {
            $this->addFlash('warning', 'Cet e-mail est déjà envoyé ou en cours d’envoi. Aucun nouvel envoi n’a été déclenché.');
        } elseif (TihApplicationEvent::EMAIL_SENT === $status) {
            $this->addFlash('success', $reviewRequest
                ? 'L’e-mail de remise en attente a été envoyé au TIH.'
                : 'L’e-mail de refus a été renvoyé au candidat.');
        } else {
            $this->addFlash('danger', 'La nouvelle tentative d’envoi a échoué. Vous pouvez réessayer ultérieurement.');
        }

        return $this->redirectToRoute('app_admin_tih');
    }

    private function submitReasonedDecision(
        Request $request,
        TihApplicationWorkflowService $workflow,
        int $id,
        bool $reviewRequest,
    ): Response {
        $tokenId = ($reviewRequest ? 'review_tih' : 'refuse_tih').$id;
        if (!$this->isCsrfTokenValid($tokenId, (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'Token de sécurité invalide.');

            return $this->redirectToRoute('app_admin_tih');
        }

        $field = $reviewRequest ? 'review_reason' : 'rejection_reason';
        $reason = (string) $request->request->get($field, '');
        if ('' === trim($reason) || 1 === preg_match('/^\s*$/u', $reason)) {
            $this->addFlash('danger', $reviewRequest
                ? 'Le motif de la remise en attente est obligatoire et ne peut pas contenir uniquement des espaces.'
                : 'Le motif du refus est obligatoire et ne peut pas contenir uniquement des espaces.');

            return $this->redirectToRoute('app_admin_tih');
        }

        $administrator = $this->getUser();
        if (!$administrator instanceof User) {
            throw $this->createAccessDeniedException();
        }

        if ($reviewRequest) {
            $event = $workflow->reopenForReview($id, $reason, $administrator);
            $created = true;
        } else {
            $outcome = $workflow->refuse($id, trim($reason), $administrator);
            $event = is_array($outcome) ? $outcome['event'] : $outcome;
            $created = is_array($outcome) && $outcome['created'];
        }

        if (null === $event) {
            throw $this->createNotFoundException('Le TIH n\'a pas été trouvé.');
        }

        if (false === $event) {
            $this->addFlash('warning', $reviewRequest
                ? 'Ce profil n’est plus validé. Aucun nouvel e-mail n’a été envoyé.'
                : 'Ce profil est validé. Utilisez la remise en attente de validation.');

            return $this->redirectToRoute('app_admin_tih');
        }

        if (!$created) {
            $this->addFlash('warning', 'Cette candidature était déjà refusée. Aucun nouvel e-mail n’a été envoyé.');

            return $this->redirectToRoute('app_admin_tih');
        }

        if (TihApplicationEvent::EMAIL_SENT === $event->getEmailStatus()) {
            $this->addFlash('success', $reviewRequest
                ? 'Le profil TIH est en attente de validation et le TIH a été notifié.'
                : 'La candidature a été refusée et le candidat a été notifié.');
        } else {
            $this->addFlash('danger', $reviewRequest
                ? 'Le profil est en attente de validation, mais l’e-mail n’a pas pu être envoyé. Vous pouvez relancer l’envoi depuis l’administration.'
                : 'La candidature a bien été refusée, mais l’e-mail n’a pas pu être envoyé. Vous pouvez relancer l’envoi depuis l’administration.');
        }

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
