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

    // Ajoutez d'autres méthodes au besoin
}