<?php

namespace App\Controller;

use App\Form\AssoRecommanderType;
use App\Service\AssoRecommanderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PaginationService;
use App\Entity\AssoRecommander;
use App\Repository\AssoRecommanderRepository;
use App\Service\HelloAssoApiService; // Service dédié pour les appels API HelloAsso
use App\Service\DataFilterAndPaginator;

class AssoRecommanderController extends AbstractController
{
    private $assoRecommanderService;
    private $assoRecommanderRepository;
    private $helloAssoApiService;

    function __construct(AssoRecommanderService $assoRecommanderService, AssoRecommanderRepository $assoRecommanderRepository, HelloAssoApiService $helloAssoApiService)
    {
        $this->assoRecommanderService = $assoRecommanderService;
        $this->assoRecommanderRepository = $assoRecommanderRepository;
        $this->helloAssoApiService = $helloAssoApiService;
    }

    #[Route('/asso/recommander/{page}', name: 'app_asso_recommander', defaults: ['page' => 1])]
    public function index(PaginationService $paginationService, int $page): Response
    {
        $pagination = $paginationService->getPaginatedData(AssoRecommander::class, $page);
        return $this->render('asso_recommander/index.html.twig', [
            'controller_name' => 'AssoRecommanderController',
            'data_forms' => $pagination['data'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'page' => $pagination['current_page'],
        ]);
    }

    #[Route('/asso/recommander/events/{organizationSlug}/{formTypes}/{page}', name: 'app_asso_evenements', defaults: ['page' => 1, 'formTypes' => 'Event'])]
    public function evenementsAssoRecommander(string $organizationSlug, int $page, string $formTypes, DataFilterAndPaginator $dataFilterAndPaginator): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}/forms?formTypes={$formTypes}";
    
        $data_forms = $this->helloAssoApiService->makeApiCall($url);
        $filteredData = $dataFilterAndPaginator->filterAndSortData($data_forms['data']);
        $paginationResult = $dataFilterAndPaginator->paginateData($filteredData, $page);
        // echo $url;
        // dump($data_forms['data']);
        // dump($paginationResult['items']);
        // exit;
        return $this->render('asso_recommander/events.html.twig', [
            'data_forms' => $paginationResult['items'],
            'total' => $paginationResult['totalItems'],
            'pages' => $paginationResult['totalPages'],
            'page' => $paginationResult['currentPage'],
        ]);
    }

    #[Route('/asso/recommander/detail/{organizationSlug}/{formType}/{formSlug}', name: 'app_asso_recommander_detail')]
    public function detailAssoRecommander(string $organizationSlug, string $formType, string $formSlug): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}/forms/{$formType}/{$formSlug}/public";
        $data_form = $this->helloAssoApiService->makeApiCall($url);

        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];

        return $this->render('billetteries\detail.html.twig', [
            'data_actu' => $data_form,
            'googleMapsApiKey' => $googleMapsApiKey,
        ]);
    }


    #[Route('/admin/asso/recommander/new', name: 'app_asso_recommander_new')]
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

                $data = $this->assoRecommanderService->updateAssoRecommanderFromApi($organizationSlug);

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
        ]);
    }
}
