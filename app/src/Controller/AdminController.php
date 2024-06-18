<?php
// src/Controller/AdminController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\HelloAssoAuthService;

#[Route('/admin')]
class AdminController extends AbstractController
{
    private $helloAssoAuthService;

    public function __construct(HelloAssoAuthService $helloAssoAuthService)
    {
        $this->helloAssoAuthService = $helloAssoAuthService;
    }
    #[Route('', name: 'admin_dashboard')]
    public function dashboard()
    {
        $bearerToken = $this->helloAssoAuthService->getToken();
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'];
        $authorization = "Bearer " . $bearerToken;

        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', $url, [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $authorization,
            ],
        ]);
        $data = json_decode($response->getBody(), true);

        return $this->render('admin/dashbord/dashboard.html.twig', [
            'data' => $data,
        ]);
    }

    #[Route('/{donnees}/{formType}/{formSlug}/{tierTypes}/{{page}', name: 'admin_api')]
    public function api(string $donnees, string $page, string $formType, string $formSlug, string $tierTypes)
    {
        if ($donnees === 'orders') {
            $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/items?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date";
            if ($formType !== '1' && $formSlug !== '1') {
                $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/forms/" . $formType . "/" . $formSlug . "/items?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date";
            }
            if ($tierTypes !== '1') {
                $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/items?pageIndex=" . $page . "&pageSize=15&tierTypes=" . $tierTypes . "&withDetails=true&sortOrder=Desc&sortField=Date";
                // https://api.helloasso.com/v5/organizations/gnut-06/items?pageIndex=1&pageSize=15Donation&withDetails=true&sortOrder=Desc&sortField=Date
            }
        }
        if ($donnees === 'payments') {
            $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/payments?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date";
            
            // 'https://api.helloasso.com/v5/organizations/gnut-06/payments?pageIndex=1&pageSize=15&sortOrder=Desc&sortField=Date'

            if ($formType !== '1') {
                // 'https://api.helloasso.com/v5/organizations/gnut-06/payments/search?pageSize=15&formType=Donation&sortOrder=Desc&sortField=Date';
                $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/payments/search?pageSize=15&formType=" . $formType . "&sortOrder=Desc&sortField=Date";
            }
        }

        // dump($url);
        //     exit;

        $bearerToken = $this->helloAssoAuthService->getToken();
        $authorization = "Bearer " . $bearerToken;

        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', $url, [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $authorization,
            ],
        ]);
        $data_forms = json_decode($response->getBody(), true);
        // dump($data_forms);
        // exit;

        return $this->render('admin/orders/index.html.twig', [
            'data_forms' => $data_forms,
        ]);
    }
}
