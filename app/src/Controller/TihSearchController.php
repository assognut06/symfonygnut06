<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Repository\TihRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/tih')]
class TihSearchController extends AbstractTihSearchController
{
    #[Route('/tih_search', name: 'app_tih_search', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('tih_search/index.html.twig', $this->getSearchViewData($request, 'app_tih_details', Tih::PROFILE_KIND_DEMO));
    }

    #[Route('/tih/{id}', name: 'app_tih_details', methods: ['GET'])]
    public function details(TihRepository $tihRepository, int $id): Response
    {
        return $this->render('tih_search/details.html.twig', [
            'tih' => $this->getTihDetailsViewModel($tihRepository, $id, Tih::PROFILE_KIND_DEMO),
        ]);
    }

    #[Route('/tih/{id}/cv', name: 'app_tih_public_cv', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadPublicCv(TihRepository $tihRepository, int $id): Response
    {
        return $this->createPublicCvResponse($tihRepository, $id, Tih::PROFILE_KIND_DEMO);
    }

    #[Route('/tih/{id}/attestation', name: 'app_tih_public_attestation', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadPublicAttestation(TihRepository $tihRepository, int $id): Response
    {
        return $this->createPublicAttestationResponse($tihRepository, $id, Tih::PROFILE_KIND_DEMO);
    }

    #[Route('/tih/{id}/contact', name: 'app_tih_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, TihRepository $tihRepository, int $id): Response
    {
        throw $this->createNotFoundException('La prise de contact est désactivée dans la démonstration.');
    }
}
