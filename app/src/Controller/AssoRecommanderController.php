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
use App\Service\SpinnerService;

class AssoRecommanderController extends AbstractController
{
    private $assoRecommanderService;
    private $assoRecommanderRepository;
    private $helloAssoApiService;
    private $spinnerService;

    function __construct(AssoRecommanderService $assoRecommanderService, 
    AssoRecommanderRepository $assoRecommanderRepository, 
    HelloAssoApiService $helloAssoApiService, SpinnerService $spinnerService)
    {
        $this->assoRecommanderService = $assoRecommanderService;
        $this->assoRecommanderRepository = $assoRecommanderRepository;
        $this->helloAssoApiService = $helloAssoApiService;
        $this->spinnerService = $spinnerService;
    }

    #[Route('/asso/recommander/liste/{page}', name: 'app_asso_recommander', defaults: ['page' => 1])]
    public function index(PaginationService $paginationService, int $page): Response
    {
        $pagination = $paginationService->getPaginatedData(AssoRecommander::class, $page);

        if(!$pagination)
            $this->spinnerService->showSpinner();
        else 
            $this->spinnerService->hideSpinner();

        return $this->render('asso_recommander/index.html.twig', [
            'controller_name' => 'AssoRecommanderController',
            'data_forms' => $pagination['data'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'page' => $pagination['current_page'],
            'spinner' => $this->spinnerService->getSpinner(),
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
}
