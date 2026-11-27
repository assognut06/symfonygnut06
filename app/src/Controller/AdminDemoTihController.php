<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Repository\TihRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class AdminDemoTihController extends AbstractController
{
    #[Route('/admin/tih-demo', name: 'app_admin_demo_tih', methods: ['GET'])]
    public function index(TihRepository $repository): Response
    {
        return $this->render('admin/admin_tih_demo/index.html.twig', [
            'tihs' => $repository->findBy(
                ['profileKind' => Tih::PROFILE_KIND_DEMO],
                ['id' => 'DESC'],
            ),
        ]);
    }

    #[Route('/admin/tih-demo/{id}/approve', name: 'app_admin_demo_tih_approve', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function approve(Request $request, Tih $tih, EntityManagerInterface $em): Response
    {
        $this->assertDemoDecision($request, $tih, 'approve_demo_tih');
        $tih->setApplicationStatus(Tih::STATUS_APPROVED);
        $em->flush();
        $this->addFlash('success', 'Le profil fictif est maintenant publié dans la démonstration.');

        return $this->redirectToRoute('app_admin_demo_tih');
    }

    #[Route('/admin/tih-demo/{id}/refuse', name: 'app_admin_demo_tih_refuse', requirements: ['id' => '\d+'], methods: ['POST'])]
    public function refuse(Request $request, Tih $tih, EntityManagerInterface $em): Response
    {
        $this->assertDemoDecision($request, $tih, 'refuse_demo_tih');
        $tih->setApplicationStatus(Tih::STATUS_REFUSED);
        $em->flush();
        $this->addFlash('success', 'La proposition de profil fictif a été refusée.');

        return $this->redirectToRoute('app_admin_demo_tih');
    }

    private function assertDemoDecision(Request $request, Tih $tih, string $tokenPrefix): void
    {
        if (!$tih->isDemo()) {
            throw $this->createNotFoundException('Profil de démonstration introuvable.');
        }

        if (!$this->isCsrfTokenValid($tokenPrefix.$tih->getId(), (string) $request->request->get('_token'))) {
            throw $this->createAccessDeniedException('Token de sécurité invalide.');
        }
    }
}
