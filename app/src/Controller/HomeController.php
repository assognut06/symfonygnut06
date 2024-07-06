<?php

namespace App\Controller;

use App\Service\HelloAssoApiService;
use App\Service\SpinnerService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class HomeController extends AbstractController
{
    private $helloAssoApiService;  // Service dédié pour les appels API HelloAsso
    private $spinnerService;

    public function __construct(HelloAssoApiService $helloAssoApiService,
    SpinnerService $spinnerService)
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->spinnerService = $spinnerService;
    }
    #[Route('/', name: 'app_home')]
    public function index(): Response
    {
        // $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'];
        // $data = $this->helloAssoApiService->makeApiCall($url);
        // $dataApi = [];
        // foreach($data as $key => $value) {
        //     $dataApi[$key] = $value;
        //     var_dump($dataApi);
        // }


        return $this->render('home/index.html.twig', [
            'controller_name' => 'HomeController',
        ]);
    }
}
