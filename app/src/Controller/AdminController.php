<?php
// src/Controller/AdminController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\HelloAssoApiService; // Service dédié pour les appels API HelloAsso


#[Route('/admin')]
class AdminController extends AbstractController
{

    private $helloAssoApiService;

    public function __construct(HelloAssoApiService $helloAssoApiService)
    {
        $this->helloAssoApiService = $helloAssoApiService;
    }
    #[Route('', name: 'admin_dashboard')]
    public function dashboard()
    {
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'];
        
        $data = $this->helloAssoApiService->makeApiCall($url);

        return $this->render('admin/dashbord/dashboard.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/{donnees}/{formType}/{formSlug}/{tierTypes}/{page}', name: 'admin_api')]
    public function api(string $donnees, string $page, string $formType, string $formSlug, string $tierTypes)
    {
        if ($donnees === 'orders') {
            $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/items?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed";
            if ($formType !== '1' && $formSlug !== '1') {
                $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/forms/" . $formType . "/" . $formSlug . "/items?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed";
            }
            if ($tierTypes !== '1') {
                $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/items?pageIndex=" . $page . "&pageSize=15&tierTypes=" . $tierTypes . "&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed";
                // https://api.helloasso.com/v5/organizations/gnut-06/items?pageIndex=1&pageSize=15Donation&withDetails=true&sortOrder=Desc&sortField=Date
            }
        }
        if ($donnees === 'payments') {
            $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/payments?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&states=Authorized";
            
            // 'https://api.helloasso.com/v5/organizations/gnut-06/payments?pageIndex=1&pageSize=15&sortOrder=Desc&sortField=Date'

            if ($formType !== '1') {
                // 'https://api.helloasso.com/v5/organizations/gnut-06/payments/search?pageSize=15&formType=Donation&sortOrder=Desc&sortField=Date';
                $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/payments/search?pageSize=15&formType=" . $formType . "&sortOrder=Desc&sortField=Date&states=Authorized";
            }
        }

        $data_forms = $this->helloAssoApiService->makeApiCall($url);
        
        return $this->render('admin/orders/index.html.twig', [
            'data_forms' => $data_forms,
        ]);
    }

    #[Route('/details/{donnees}/{id}', name: 'admin_details_show')]
    public function details(string $donnees, string $id)
    {
        if ($donnees === 'orders') {
            $url = "https://api.helloasso.com/v5/items/" . $id;
        }
        if ($donnees === 'payments') {
            $url = "https://api.helloasso.com/v5/payments/" . $id;
        }

        $data_forms = $this->helloAssoApiService->makeApiCall($url);

        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];

        if($donnees === 'orders') {
            return $this->render('admin/orders/detailsOrder.html.twig', [
                'data_forms' => $data_forms,
                'googleMapsApiKey' => $googleMapsApiKey,
            ]);
        }
        if($donnees === 'payments') {
            return $this->render('admin/orders/detailsPayment.html.twig', [
                'data_forms' => $data_forms,
                'googleMapsApiKey' => $googleMapsApiKey,
            ]);
        }
       
    }
}
