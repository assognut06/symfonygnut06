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
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{

    private $helloAssoApiService;
    private $entityManager;
    private string $slugAsso;
    private string $googleMapsApiKey;

    public function __construct(
        HelloAssoApiService $helloAssoApiService, 
        EntityManagerInterface $entityManager,
        string $slugAsso,
        string $googleMapsApiKey
    )
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->entityManager = $entityManager;
        $this->slugAsso = $slugAsso;
        $this->googleMapsApiKey = $googleMapsApiKey;
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

        $page = max(1, $request->query->getInt('page', 1));
        $url = "https://api.helloasso.com/v5/organizations/{$this->slugAsso}/items?userSearchKey=" . $userEmail . "&pageIndex=" . $page . "&pageSize=4&withDetails=false&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true";

        $data_items = $this->helloAssoApiService->makeApiCall($url);
        
        // Renvoyer à la vue Twig, en passant l'utilisateur comme variable
        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'data_items' => $data_items,
            'googleMapsApiKey' => $this->googleMapsApiKey,
            'profileForm' => $form->createView(),
        ]);
    }

    #[Route(
        '/{donnees}/{page}',
        name: 'app_profil_page',
        requirements: ['donnees' => 'orders|payments', 'page' => '\d+'],
        defaults: ['page' => 1],
        methods: ['GET']
    )]
    public function page(int $page, string $donnees): Response
    {
        // Assurez-vous que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');

        // Récupérer l'utilisateur connecté
        $user = $this->getUser();
        $userEmail = urlencode($user->getUserIdentifier());
        $base = $donnees === 'orders' ? "items" : "payments";
        $url = $this->buildHelloAssoUrl($base, $userEmail, $page, $donnees);

        $data_items = $this->helloAssoApiService->makeApiCall($url);
        // dump($user);
        // exit;
        // Renvoyer à la vue Twig, en passant l'utilisateur comme variable
        return $this->render('profil/index.html.twig', [
            'user' => $user,
            'data_items' => $data_items,
            'googleMapsApiKey' => $this->googleMapsApiKey,
        ]);
    }

    // Fonction pour construire l'URL de base
    private function buildHelloAssoUrl(string $base, string $userEmail, int $page, string $type): string
    {
        $pageSize = 4;
        $sortOrder = "Desc";
        $sortField = "Date";
        $url = "https://api.helloasso.com/v5/organizations/{$this->slugAsso}/$base?userSearchKey=$userEmail&pageIndex=$page&pageSize=$pageSize&withDetails=false&sortOrder=$sortOrder&sortField=$sortField&withCount=true";
    
        if ($type === 'orders') {
            $url .= "&itemStates=Processed";
        } elseif ($type === 'payments') {
            $url .= "&states=Authorized";
        }
    
        return $url;
    }
}
