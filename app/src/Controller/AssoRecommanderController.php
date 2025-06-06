<?php

namespace App\Controller;

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

#[Route('/assos/recommandees')]
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

    #[Route('/liste/{page}', name: 'app_asso_recommander', defaults: ['page' => 1])]
    public function index(PaginationService $paginationService, int $page): Response
    {
        $pagination = $paginationService->getPaginatedData(AssoRecommander::class, $page);
        return $this->render('asso_recommander/index.html.twig', [
            'controller_name' => 'AssoRecommanderController',
            'data_forms' => $pagination['data'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'page' => $pagination['current_page'],
            'cities' => $pagination['cities'],
        ]);
    }

    #[Route('/ville/{city}/{page}', name: 'app_asso_recommander_ville', defaults: ['page' => 1])]
    public function assoRecommanderByCity(PaginationService $paginationService,string $city, int $page): Response
    {
        $pagination = $paginationService->getPaginatedDataCity(AssoRecommander::class, $page, ['city' => $city]);
        return $this->render('asso_recommander/index.html.twig', [
            'data_forms' => $pagination['data'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'page' => $pagination['current_page'],
            'city' => $city,
            'cities' => $pagination['cities'],
        ]);
    }

    #[Route('/search', name: 'app_asso_recommander_search')]
    public function search(Request $request,PaginationService $paginationService): Response
    {
        $query = $request->query->get('query');
        $pagination = $paginationService->getPaginatedDataSearch(AssoRecommander::class, $query, 1);
        return $this->render('asso_recommander/index.html.twig', [
            'data_forms' => $pagination['data'],
            'total' => $pagination['total'],
             'pages' => 1,
            'page' => 1,
        ]);
    }

    #[Route('/events/{organizationSlug}/{formTypes}/{page}', name: 'app_asso_evenements', defaults: ['page' => 1, 'formTypes' => 'Event'])]
    public function evenementsAssoRecommander(string $organizationSlug, int $page, string $formTypes, DataFilterAndPaginator $dataFilterAndPaginator): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}/forms?formTypes={$formTypes}";
    
        $data_forms = $this->helloAssoApiService->makeApiCall($url);
        if ($formTypes === 'Event') {
            $filteredData = $dataFilterAndPaginator->filterAndSortData($data_forms['data']);
        }
        elseif ($formTypes === 'Membership' || $formTypes === 'CrowdFunding') {
            $filteredData = $dataFilterAndPaginator->filterMemberShipSortData($data_forms['data']);
        }
        else {
            $filteredData = $data_forms['data'];
        }
        
        $paginationResult = $dataFilterAndPaginator->paginateData($filteredData, $page);

        return $this->render('asso_recommander/events.html.twig', [
            'data_forms' => $paginationResult['items'],
            'total' => $paginationResult['totalItems'],
            'pages' => $paginationResult['totalPages'],
            'page' => $paginationResult['currentPage'],
        ]);
    }

    #[Route('/detail/{organizationSlug}/{formType}/{formSlug}', name: 'app_asso_recommander_detail')]
    public function detailAssoRecommander(string $organizationSlug, string $formType, string $formSlug): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}/forms/{$formType}/{$formSlug}/public";
        $data_form = $this->helloAssoApiService->makeApiCall($url);
// dd($url);
        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];

        return $this->render('asso_recommander/detailsEvent.html.twig', [
            'data_actu' => $data_form,
            'googleMapsApiKey' => $googleMapsApiKey,
        ]);
    }

    #[Route('/asso/recommander/fiche/{organizationSlug}', name: 'app_asso_recommander_fiche')]
    public function ficheAssoRecommander(string $organizationSlug): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}";
        $data = $this->helloAssoApiService->makeApiCall($url);

        return $this->render('asso_recommander/fiche.html.twig', [
            'data' => $data,
        ]);
    }
}
