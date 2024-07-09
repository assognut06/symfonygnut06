<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Service\HelloAssoApiService; // Service dédié pour les appels API HelloAsso
use App\Service\DataFilterAndPaginator;

#[Route('/evenements')]
class BilletteriesController extends AbstractController
{
    private $helloAssoApiService;
    private $dataFilterAndPaginator;

    public function __construct(HelloAssoApiService $helloAssoApiService, DataFilterAndPaginator $dataFilterAndPaginator)
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->dataFilterAndPaginator = $dataFilterAndPaginator;
    }
    
    #[Route('/{page}', name: 'app_billetteries', defaults: ['page' => 1])]
    public function index(int $page): Response
    {

        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/forms?states=&formTypes=Event";
        $data_forms = $this->helloAssoApiService->makeApiCall($url);
        $filteredData = $this->dataFilterAndPaginator->filterAndSortData($data_forms['data']);
        $paginationResult = $this->dataFilterAndPaginator->paginateData($filteredData, $page);

        return $this->render('billetteries/index.html.twig', [
            'data_forms' => $paginationResult['items'],
            'controller_name' => 'Billetteries Gnut 06',
            'total' => $paginationResult['totalItems'],
            'pages' => $paginationResult['totalPages'],
            'page' => $paginationResult['currentPage'],
        ]);
    }

    #[Route('/{formType}/{slug}', name: 'app_billetteries_detail')]
    public function detail(string $formType, string $slug, Request $request): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO']  . "/forms/" . $formType . "/" . $slug . "/public";

        $data_form = $this->helloAssoApiService->makeApiCall($url);

        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];

        return $this->render('billetteries/detail.html.twig', [
            'googleMapsApiKey' => $googleMapsApiKey,
            'data_actu' => $data_form,
            'controller_name' => 'Billetteries Gnut 06',
        ]);
    }
}
