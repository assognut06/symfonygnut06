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
        $bearerToken = $this->helloAssoAuthService->getToken();
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO']  ."/forms";
        $authorization = "Bearer " . $bearerToken;
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
        ]);
    }
}
