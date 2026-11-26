<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Repository\TihRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/intranet/tih')]
class IntranetTihSearchController extends AbstractTihSearchController
{
    #[Route('/tih_search', name: 'intranet_tih_search', methods: ['GET'])]
    public function index(Request $request): Response
    {
        return $this->render('tih_search/intranet/index.html.twig', $this->getSearchViewData($request, 'intranet_tih_details', Tih::PROFILE_KIND_DEMO));
    }

    #[Route('/tih/{id}', name: 'intranet_tih_details', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function details(TihRepository $tihRepository, int $id): Response
    {
        return $this->render('tih_search/intranet/details.html.twig', [
            'tih' => $this->getTihDetailsViewModel($tihRepository, $id, Tih::PROFILE_KIND_DEMO),
        ]);
    }

    #[Route('/tih/{id}/cv', name: 'intranet_tih_public_cv', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadPublicCv(TihRepository $tihRepository, int $id): Response
    {
        return $this->createPublicCvResponse($tihRepository, $id, Tih::PROFILE_KIND_DEMO);
    }

    #[Route('/tih/{id}/attestation', name: 'intranet_tih_public_attestation', requirements: ['id' => '\d+'], methods: ['GET'])]
    public function downloadPublicAttestation(TihRepository $tihRepository, int $id): Response
    {
        return $this->createPublicAttestationResponse($tihRepository, $id, Tih::PROFILE_KIND_DEMO);
    }

    #[Route('/tih/{id}/contact', name: 'intranet_tih_contact', requirements: ['id' => '\d+'], methods: ['GET', 'POST'])]
    public function contact(Request $request, TihRepository $tihRepository, int $id): Response
    {
        throw $this->createNotFoundException('La prise de contact est désactivée dans la démonstration.');
    }
}
