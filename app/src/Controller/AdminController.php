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

    #[Route('/{donnees}/{page}', name: 'admin_api')]
    public function api(string $donnees, string $page)
    {
        if ($donnees == 'orders') {
            $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO'] . "/items?pageIndex=" . $page . "&pageSize=20&withDetails=true&sortOrder=Desc&sortField=Date";
        }

        $bearerToken = $this->helloAssoAuthService->getToken();

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

        return $this->render('admin/orders/index.html.twig', [
            'data_forms' => $data_forms,
        ]);
    }

}
