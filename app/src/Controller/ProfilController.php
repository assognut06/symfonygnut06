<?php
// src/Controller/ProfilController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use App\Service\HelloAssoAuthService;
use Symfony\Component\HttpFoundation\Request;
use App\Service\HelloAssoApiService; // Service dédié pour les appels API HelloAsso
use App\Entity\Payers;
use App\Form\ProfileType;
use Doctrine\ORM\EntityManagerInterface;

#[Route('/profil')]
class ProfilController extends AbstractController
{

    private $helloAssoApiService;
    private $entityManager;

    public function __construct(
        HelloAssoApiService $helloAssoApiService, 
        EntityManagerInterface $entityManager
    )
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->entityManager = $entityManager;
    }

    #[Route('', name: 'app_profil', methods: ['GET', 'POST'])]
    public function index(Request $request): Response
    {
        // Assurez-vous que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        $userEmail = urlencode($user->getUserIdentifier());

        // Find or create Payers entity
        $payer = $this->entityManager->getRepository(Payers::class)
            ->findOneBy(['email' => $user->getUserIdentifier()]);

        if (!$payer) {
            $payer = new Payers();
            $payer->setEmail($user->getUserIdentifier());
            $payer->setCreatedAt(new \DateTimeImmutable());
        }

        // Create and handle form
        $form = $this->createForm(ProfileType::class, $payer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $payer->setUpdatedAt(new \DateTime());
            $this->entityManager->persist($payer);
            $this->entityManager->flush();

            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès !');
            
            return $this->redirectToRoute('app_profil');
        }

        $page = $request->query->get('page', 1);
        $url = "https://api.helloasso.com/v5/organizations/" . $_ENV['SLUGASSO']  . "/items?userSearchKey=" . $userEmail . "&pageIndex=" . $page . "&pageSize=4&withDetails=false&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true";

        $data_items = $this->helloAssoApiService->makeApiCall($url);
        
        $googleMapsApiKey = $_ENV['GNUT06MAPAPI'];
        
        // Renvoyer à la vue Twig, en passant l'utilisateur comme variable
        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'data_items' => $data_items,
            'googleMapsApiKey' => $googleMapsApiKey,
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route('/{donnees}/{page}', name: 'app_profil_page', defaults: ['page' => 1])]
    public function page(string $page, string $donnees): Response
    {
        // Assurez-vous que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        $userEmail = urlencode($user->getUserIdentifier());
        // Utilisation de la fonction pour construire l'URL
        if ($donnees === 'orders' || $donnees === 'payments') {
            $base = $donnees === 'orders' ? "items" : "payments";
            $url = self::buildHelloAssoUrl($base, $userEmail, $page, $donnees);
        }

        $data_items = $this->helloAssoApiService->makeApiCall($url);
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

    // Fonction pour construire l'URL de base
    private static function buildHelloAssoUrl($base, $userEmail, $page, $type)
    {
        $slugAsso = $_ENV['SLUGASSO'];
        $pageSize = 4;
        $sortOrder = "Desc";
        $sortField = "Date";
        $url = "https://api.helloasso.com/v5/organizations/$slugAsso/$base?userSearchKey=$userEmail&pageIndex=$page&pageSize=$pageSize&withDetails=false&sortOrder=$sortOrder&sortField=$sortField&withCount=true";
    
        if ($type === 'orders') {
            $url .= "&itemStates=Processed";
        } elseif ($type === 'payments') {
            $url .= "&states=Authorized";
        }
    
        return $url;
    }
}
