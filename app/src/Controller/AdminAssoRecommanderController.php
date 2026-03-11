<?php

namespace App\Controller;

use App\Entity\AssoRecommander;
use App\Form\AssoRecommanderType;
use App\Repository\AssoRecommanderRepository; // Add this line
use App\Service\AssoRecommanderService;
use App\Service\PaginationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/admin')]
class AdminAssoRecommanderController extends AbstractController
{
    private AssoRecommanderService $assoRecommanderService;
    private AssoRecommanderRepository $assoRecommanderRepository;

    public function __construct(AssoRecommanderService $assoRecommanderService, AssoRecommanderRepository $assoRecommanderRepository)
    {
        $this->assoRecommanderService = $assoRecommanderService;
        $this->assoRecommanderRepository = $assoRecommanderRepository;
    }

    #[Route('/asso/recommander/liste/{page}', name: 'app_admin_asso_recommander', defaults: ['page' => 1])]
    public function index(PaginationService $paginationService, int $page): Response
    {
        $pagination = $paginationService->getPaginatedData(AssoRecommander::class, $page);

        return $this->render('admin/asso_recommander/index.html.twig', [
            'assos' => $pagination['data'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'page' => $pagination['current_page'],
            'loading' => false,
        ]);
    }

    #[Route('/asso/recommander/new', name: 'app_asso_recommander_new')]
    public function new(Request $request): Response
    {
        // $assoRecommander = new AssoRecommander();
        $form = $this->createForm(AssoRecommanderType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $organizationSlug = $form->get('organizationSlug')->getData();

            // Vérifier si l'organizationSlug existe déjà
            if ($this->assoRecommanderRepository->existsByOrganizationSlug($organizationSlug)) {
                // Ajouter un message flash pour informer l'utilisateur
                $this->addFlash('danger', 'Le slug de l\'organisation existe déjà.');

                return $this->redirectToRoute('app_asso_recommander_new');
            } else {
                $data = $this->assoRecommanderService->createdAssoRecommanderFromApi($organizationSlug);

                if ($data) {
                    $this->addFlash('success', 'L\'association a bien été recommandée !');
                } else {
                    $this->addFlash('danger', 'L\'association n\'a pas été trouvée !');
                }

                return $this->redirectToRoute('app_asso_recommander_new');
            }
        }

        return $this->render('admin/asso_recommander/new.html.twig', [
            'form' => $form->createView(),
            'loading' => false,
        ]);
    }

    #[Route('/update-data', name: 'app_asso_update_data')]
    public function updateData(): RedirectResponse
    {
        $assoRecommander = [];
        foreach ($this->assoRecommanderRepository->findAll() as $asso) {
            $assoRecommander = $asso;
            $this->assoRecommanderService->updateAssoRecommanderFromApi($assoRecommander);
        }
        $this->addFlash('success', 'Les données des l\'associations ont été mises à jour avec succès.');

        return $this->redirectToRoute('app_admin_asso_recommander');
    }
}
