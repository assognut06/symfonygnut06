<?php

// src/Controller/AdminController.php

namespace App\Controller;

use App\Service\HelloAssoApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route; // Service dédié pour les appels API HelloAsso

#[Route('/admin')]
class AdminController extends AbstractController
{
    private HelloAssoApiService $helloAssoApiService;

    public function __construct(HelloAssoApiService $helloAssoApiService)
    {
        $this->helloAssoApiService = $helloAssoApiService;
    }

    #[Route('', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $url = 'https://api.helloasso.com/v5/organizations/'.$_ENV['SLUGASSO'];

        $data = $this->helloAssoApiService->makeApiCall($url);

        return $this->render('admin/dashbord/dashboard.html.twig', [
            'data' => $data,
            'loading' => false,
        ]);
    }

    #[Route('/{donnees}/{formType}/{formSlug}/{tierTypes}/{page}', name: 'admin_api')]
    public function api(string $donnees, string $page, string $formType, string $formSlug, string $tierTypes): Response
    {
        $url = $this->buildApiUrl($donnees, $page, $formType, $formSlug, $tierTypes);

        $data_forms = $this->helloAssoApiService->makeApiCall($url);

        return $this->render('admin/orders/index.html.twig', [
            'data_forms' => $data_forms,
            'loading' => false,
        ]);
    }

    #[Route('/details/{donnees}/{id}', name: 'admin_details_show')]
    public function details(string $donnees, string $id): Response
    {
        $url = $this->buildDetaislUrl($donnees, $id);

        $data_forms = $this->helloAssoApiService->makeApiCall($url);

        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];

        if ('orders' === $donnees) {
            return $this->render('admin/orders/detailsOrder.html.twig', [
                'data_forms' => $data_forms,
                'googleMapsApiKey' => $googleMapsApiKey,
                'loading' => false,
            ]);
        }
        // if ('payments' === $donnees) {
        else {
            return $this->render('admin/orders/detailsPayment.html.twig', [
                'data_forms' => $data_forms,
                'googleMapsApiKey' => $googleMapsApiKey,
                'loading' => false,
            ]);
        }
    }

    private function buildApiUrl(string $donnees, string $page, string $formType, string $formSlug, string $tierTypes): string
    {
        $baseUrl = 'https://api.helloasso.com/v5/organizations/'.$_ENV['SLUGASSO'];
        $url = '';

        switch ($donnees) {
            case 'orders':
                $url = $baseUrl.'/items?pageIndex='.$page.'&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true';
                if ('1' !== $formType && '1' !== $formSlug) {
                    $url = $baseUrl.'/forms/'.$formType.'/'.$formSlug.'/items?pageIndex='.$page.'&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true';
                }
                if ('1' !== $tierTypes) {
                    $url = $baseUrl.'/items?pageIndex='.$page.'&pageSize=15&tierTypes='.$tierTypes.'&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true';
                }
                break;
            case 'payments':
                $url = $baseUrl.'/payments?pageIndex='.$page.'&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&states=Authorized&withCount=true';
                if ('1' !== $formType) {
                    $url = $baseUrl.'/payments/search?pageSize=15&formType='.$formType.'&sortOrder=Desc&sortField=Date&states=Authorized&withCount=true';
                }
                break;
        }

        return $url;
    }

    private function buildDetaislUrl(string $type, string $id): string
    {
        $baseUrl = 'https://api.helloasso.com/v5';
        switch ($type) {
            case 'orders':
                return $baseUrl.'/items/'.$id;
            case 'payments':
                return $baseUrl.'/payments/'.$id;
            default:
                throw new \InvalidArgumentException('Type non pris en charge: '.$type);
        }
    }
}
