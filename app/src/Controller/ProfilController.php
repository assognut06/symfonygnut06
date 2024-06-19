<?php
// src/Controller/ProfilController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\HelloAssoAuthService;
use Symfony\Component\HttpFoundation\Request;
#[Route('/profil')]
class ProfilController extends AbstractController
{
    private $helloAssoAuthService;

    public function __construct(HelloAssoAuthService $helloAssoAuthService)
    {
        $this->helloAssoAuthService = $helloAssoAuthService;
    }

    #[Route('', name: 'app_profil')]
    public function index(Request $request): Response
    {
        // Assurez-vous que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        $userEmail = urlencode($user->getUserIdentifier());
        $page = $request->query->get('page', 1);
        $bearerToken = $this->helloAssoAuthService->getToken();
        $url="https://api.helloasso.com/v5/organizations/". $_ENV['SLUGASSO']  ."/items?userSearchKey=" . $userEmail . "&pageIndex=". $page . "&pageSize=4&withDetails=false&sortOrder=Desc&sortField=Date&itemStates=Processed";
        $authorization = "Bearer " . $bearerToken;

        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', $url, [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $authorization,
            ],
        ]);
        $data_items= json_decode($response->getBody(), true);
        // dump($user);
        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];
        // exit;
        // Renvoyer à la vue Twig, en passant l'utilisateur comme variable
        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'data_items' => $data_items,
            'googleMapsApiKey' => $googleMapsApiKey,
        ]);
    }

    #[Route('/{donnees}/{page}', name: 'app_profil_page')]
    public function page(string $page, string $donnees): Response
    {
        // Assurez-vous que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        if ($page < 1) {
            $page = 1;
        }

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        $userEmail = urlencode($user->getUserIdentifier());
        // $page = $request->query->get('page', 1);
        if ($donnees === 'orders') {
            $url="https://api.helloasso.com/v5/organizations/". $_ENV['SLUGASSO']  ."/items?userSearchKey=" . $userEmail . "&pageIndex=". $page . "&pageSize=4&withDetails=false&sortOrder=Desc&sortField=Date&itemStates=Processed";
        } elseif ($donnees === 'payments') {
            $url="https://api.helloasso.com/v5/organizations/". $_ENV['SLUGASSO']  ."/payments?userSearchKey=" . $userEmail . "&pageIndex=". $page . "&pageSize=4&withDetails=false&sortOrder=Desc&sortField=Date&states=Authorized";
            https://api.helloasso.com/v5/organizations/gnut-06/payments?userSearchKey=contact%40cgmnice.com&pageIndex=1&pageSize=20&states=Authorized&sortOrder=Desc&sortField=Date
        }
        $bearerToken = $this->helloAssoAuthService->getToken();

        
        $authorization = "Bearer " . $bearerToken;

        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', $url, [
            'headers' => [
                'accept' => 'application/json',
                'authorization' => $authorization,
            ],
        ]);
        $data_items= json_decode($response->getBody(), true);
        // dump($user);
        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];
        // exit;
        // Renvoyer à la vue Twig, en passant l'utilisateur comme variable
        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'data_items' => $data_items,
            'googleMapsApiKey' => $googleMapsApiKey,
        ]);
    }
}