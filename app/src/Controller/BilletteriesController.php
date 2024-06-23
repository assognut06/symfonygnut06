<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\KernelInterface;
use DateTime;
use App\Service\HelloAssoAuthService;
use App\Service\HelloAssoApiService; // Service dédié pour les appels API HelloAsso

class BilletteriesController extends AbstractController
{
    private $helloAssoAuthService;
    private $helloAssoApiService;
    
    public function __construct(HelloAssoAuthService $helloAssoAuthService, HelloAssoApiService $helloAssoApiService)
    {
        $this->helloAssoAuthService = $helloAssoAuthService;
        $this->helloAssoApiService = $helloAssoApiService;
    }
    #[Route('/billetteries/{page}', name: 'app_billetteries', defaults: ['page' => 1])]
    public function index(KernelInterface $kernel, int $page): Response
    {

        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO']  ."/forms?states=&formTypes=Event";

        $data_forms = $this->helloAssoApiService->makeApiCall($url);

        // Supposons que $data_forms['data'] contient les données que vous avez mentionnées
        $filteredData = array_filter($data_forms['data'], function ($entry) {

            $endDate = DateTime::createFromFormat(DateTime::ISO8601, $entry['endDate']);
            $now = new DateTime();

            return $endDate > $now;
        });

        usort($filteredData, function ($a, $b) {
            $dateA = DateTime::createFromFormat(DateTime::ISO8601, $a['endDate']);
            $dateB = DateTime::createFromFormat(DateTime::ISO8601, $b['endDate']);

            if ($dateA == $dateB) {
                return 0;
            }

            return ($dateA < $dateB) ? -1 : 1;
        });

        $itemsPerPage = 6;
        $totalItems = count($filteredData);
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Calculer l'index de départ pour la page actuelle
        $start = ($page - 1) * $itemsPerPage;
        
        // Extraire les éléments pour la page actuelle
        $pageItems = array_slice($filteredData, $start, $itemsPerPage);

        return $this->render('billetteries/index.html.twig', [
            'data_forms' => $pageItems,
            'controller_name' => 'Billetteries Gnut 06',
            'total' => $totalItems,
            'pages' => $totalPages,
            'page' => $page,
           
        ]);
    }

    #[Route('/billetteries/{formType}/{slug}', name: 'app_billetteries_detail')]
    public function detail(string $formType, string $slug, Request $request): Response
    {  
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO']  ."/forms/" . $formType . "/" . $slug . "/public";
       
        $data_form = $this->helloAssoApiService->makeApiCall($url);

        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];
    
        return $this->render('billetteries/detail.html.twig', [
            'googleMapsApiKey' => $googleMapsApiKey,
            'data_actu' => $data_form,
            'controller_name' => 'Billetteries Gnut 06',
        ]);
    }
}
