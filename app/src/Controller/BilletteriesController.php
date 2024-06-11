<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use DateTime;
use App\Service\HelloAssoAuthService;
use GuzzleHttp\Client;

class BilletteriesController extends AbstractController
{
    private $helloAssoAuthService;

    public function __construct(HelloAssoAuthService $helloAssoAuthService)
    {
        $this->helloAssoAuthService = $helloAssoAuthService;
    }
    #[Route('/billetteries', name: 'app_billetteries')]
    public function index(Request $request, SessionInterface $session, KernelInterface $kernel): Response
    {
        // $client_token =  new \GuzzleHttp\Client();
        // $response = $client_token->request('POST', 'https://api.helloasso.com/oauth2/token', [
        //     'form_params' => [
        //         'grant_type' => 'client_credentials',
        //         'client_id' => $_ENV['APICLIENTID'],
        //         'client_secret' => $_ENV['APICLIENTSECRET'],
        //     ],
        // ]);
        // $dataToken = json_decode($response->getBody(), true);
        // $session->set('bearer_token', $dataToken['access_token']);
        // $session->set('expiration_token', (new DateTime())->modify('+' . $dataToken['expires_in'] . ' seconds'));
        // $session->set('refresh_token', $dataToken['refresh_token']);
        // $session->set('bearer_token', 'my_bearer_token');
        // $bearer_token = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI0YWU4Y2JmMGEwMTc0MzAyMmQwNjA4ZGM4ODQxNTg1MiIsInVycyI6Ik9yZ2FuaXphdGlvbkFkbWluIiwiY3BzIjpbIkFjY2Vzc1B1YmxpY0RhdGEiLCJBY2Nlc3NUcmFuc2FjdGlvbnMiLCJDaGVja291dCJdLCJuYmYiOjE3MTc5NzI5NzAsImV4cCI6MTcxNzk3NDc3MCwiaXNzIjoiaHR0cHM6Ly9hcGkuaGVsbG9hc3NvLmNvbSIsImF1ZCI6IjZiZWJiYTU5ZWZhMTQ0Njk4ZWFhYzc4NGY0ZGUxZmNmIn0.VSe9kFGWVfcIHxbBWANVP79aHcdUnlA5sbg8sqQuiJuI8LnW698JIYu_RMFfhmSFc0NqUS50hiJouf44Ds5X1dMR6pDX0d397pp3PZthJVUm3dx3QESsad3oVNYaor2_jjxnpcbdvWwnXMGChE_vOWfwAFxzd-RvCdPijNLseJx-PabB-fIWE_QAubZhe3_86hP0kjF4AT3jK0vMp8uC0wK4elbx5_Tp4tFWaLCDpi1P7Q8SpvAkwj6q4VGR3SNsJeEMP2-trJhbpiwArOg2h7Co6MxIjkgjJT4UX8xsc4IXAepgcMO1eotokEqUoB_UIoMzNmpSYxcMTbzLmROZHA";
        $bearerToken = $this->helloAssoAuthService->getToken();
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO']  ."/forms";
        $authorization = "Bearer " . $bearerToken;

        // $curl = curl_init();

        // // if($_SERVER['HTTP_HOST']=='127.0.0.1'){
        // //     curl_setopt_array($curl, [
        // //         CURLOPT_SSL_VERIFYPEER => 0,
        // //         CURLOPT_SSL_VERIFYHOST => 0,
        // //     ]);
        // // }
        // curl_setopt_array($curl, [

        //     CURLOPT_URL => $url,
        //     CURLOPT_RETURNTRANSFER => true,
        //     CURLOPT_HTTPGET => true,
        //     CURLOPT_HTTPHEADER => [
        //         "Authorization: $authorization"
        //     ],
        // ]);

        // $response = curl_exec($curl);
        // $err = curl_error($curl);
        // if (!$response) {
        //     echo "cURL Error or no response: " . curl_error($curl);
        // } else {
        //     echo "Raw response: " . $response;
        // }
        // curl_close($curl);
        // $data_forms = json_decode($response);
        // if ($err) {
        //     echo "cURL Error #:" . $err;
        // }
        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', $url, [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $authorization,
            ],
        ]);
        $data_forms = json_decode($response->getBody(), true);
        // Supposons que $data_forms['data'] contient les données que vous avez mentionnées
        $filteredData = array_filter($data_forms['data'], function ($entry) {
            if ($entry['formType'] !== "Event") {
                return false;
            }

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

        // À ce stade, $filteredData est trié par endDate de la plus proche à la plus éloignée de la date actuelle.
        // $filteredData contient maintenant uniquement les entrées correspondant à vos critères
        // dump($filteredData);
        // dump($data_forms);
        // Dans votre méthode de contrôleur
        // $allSessionData = $session->all();
        // dump($allSessionData);
        // dump($bearerToken); // Utilisez dump() pour Symfony ou error_log() pour un log simple
        // dump($bearer_token);
        // exit;

        $projectDir = $kernel->getProjectDir();
        $dir = $projectDir . '/public/images/news'; // Chemin vers le répertoire des images
        $images = glob($dir . '/*.jpeg'); // Récupère tous les fichiers JPEG

        if (!empty($images)) {
            $randomImage = $images[array_rand($images)]; // Sélectionne aléatoirement une image
            // Convertit le chemin système en chemin relatif pour le web
            $webPath = str_replace($projectDir . '/public', '', $randomImage);
            // Assurez-vous que le chemin commence par '/'
            $webPath = '/' . ltrim($webPath, '/');
        }

        return $this->render('billetteries/index.html.twig', [
            'random_image' => $webPath,
            'data_forms' => $filteredData,
            'controller_name' => 'Billetteries Gnut 06',
            'currentDate' => new \DateTime(),
            'bearer_token' => $bearerToken // Passer le token à la vue
        ]);
    }

    #[Route('/billetteries/{formType}/{slug}', name: 'app_billetteries_detail')]
    public function detail(string $formType, string $slug, Request $request): Response
    {
        // Vous pouvez accéder aux variables $formType et $slug directement dans cette méthode.
        // Ici, vous pouvez ajouter votre logique pour traiter les données en fonction de $formType et $slug.
        
        // Exemple d'utilisation:
        // $data = $this->someService->getDataBasedOnTypeAndSlug($formType, $slug);
        $bearerToken = $this->helloAssoAuthService->getToken();
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO']  ."/forms/" . $formType . "/" . $slug . "/public";
        $authorization = "Bearer " . $bearerToken;

        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', $url, [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $authorization,
            ],
        ]);
        $data_form = json_decode($response->getBody(), true);

        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];
    
        return $this->render('billetteries/detail.html.twig', [
            // 'data' => $data,
            'googleMapsApiKey' => $googleMapsApiKey,
            'data_actu' => $data_form,
            'controller_name' => 'Billetteries Gnut 06',
            'currentDate' => new \DateTime(),
        ]);
    }
}
