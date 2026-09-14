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
use App\Entity\User;
use App\Form\ProfileType;
use App\Repository\PayersRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/profil')]
#[IsGranted('ROLE_USER')]
class ProfilController extends AbstractController
{

    private HelloAssoApiService $helloAssoApiService;
    private EntityManagerInterface $entityManager;
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
        /** @var User $user */
        $user = $this->getUser();
        if ((null == $user) || !$user->isVerified()) {
            throw $this->createAccessDeniedException('Veuillez valider votre adresse email pour accéder à votre profil.');
        }

        $userEmail = urlencode($user->getUserIdentifier());

        $payer = $this->getOrCreatePayer($user);

        // Create and handle form
        $form = $this->createForm(ProfileType::class, $payer);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            try {
                $payer->setUpdatedAt(new \DateTime());
                $this->entityManager->persist($payer);
                $this->entityManager->flush();
            } catch (\Throwable $e) {
                error_log(sprintf('Erreur mise a jour profil (user_id=%s): %s', (string) $user->getId(), $e->getMessage()));
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'enregistrement de vos informations. Veuillez réessayer.');

                return $this->redirectToRoute('app_profil');
            }

            $this->addFlash('success', 'Vos informations ont été mises à jour avec succès !');
            
            return $this->redirectToRoute('app_profil');
        }

        $page = max(1, $request->query->getInt('page', 1));
        $url = "https://api.helloasso.com/v5/organizations/{$this->slugAsso}/items?userSearchKey=" . $userEmail . "&pageIndex=" . $page . "&pageSize=4&withDetails=false&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true";

        $data_items = $this->makeSafeHelloAssoCall($url);
        
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
        /** @var User $user */
        $user = $this->getUser();
        $userEmail = urlencode($user->getUserIdentifier());
        $base = 'orders' === $donnees ? "items" : "payments";
        $url = $this->buildHelloAssoUrl($base, $userEmail, $page, $donnees);

        $data_items = $this->makeSafeHelloAssoCall($url);
        if ('payments' === $donnees) {
            $data_items = $this->filterPaymentsByConnectedUser($data_items, $user->getUserIdentifier());
        }
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
    
        if ('orders' === $type) {
            $url .= "&itemStates=Processed";
        } elseif ('payments' === $type) {
            $url .= "&states=Authorized";
        }
    
        return $url;
    }

    private function getOrCreatePayer(User $user): Payers
    {
        /** @var PayersRepository $repository */
        $repository = $this->entityManager->getRepository(Payers::class);
        $payer = $repository->findOneBy(['email' => $user->getUserIdentifier()]);

        if ($payer instanceof Payers) {
            return $payer;
        }

        $payer = new Payers();
        $payer->setEmail($user->getUserIdentifier());
        $payer->setCreatedAt(new \DateTimeImmutable());

        return $payer;
    }
    /**
     * @return array<mixed>
     */
    private function makeSafeHelloAssoCall(string $url): array
    {
        try {
            $data = $this->helloAssoApiService->makeApiCall($url);
        } catch (\Throwable $e) {
            error_log(sprintf('Erreur appel HelloAsso profil: %s', $e->getMessage()));

            return ['data' => []];
        }

        return is_array($data) ? $data : ['data' => []];
    }

    /**
     * @param array<mixed> $dataItems
     *
     * @return array<mixed>
     */
    private function filterPaymentsByConnectedUser(array $dataItems, string $userIdentifier): array
    {
        $normalizedUserEmail = mb_strtolower(trim($userIdentifier));
        $filteredPayments = array_filter(
            $dataItems['data'] ?? [],
            static function (mixed $payment) use ($normalizedUserEmail): bool {
                if (!is_array($payment)) {
                    return false;
                }

                $payer = $payment['payer'] ?? null;
                if (!is_array($payer)) {
                    return false;
                }

                $payerEmail = mb_strtolower(trim((string) ($payer['email'] ?? '')));

                return '' !== $payerEmail && hash_equals($normalizedUserEmail, $payerEmail);
            }
        );

        $dataItems['data'] = array_values($filteredPayments);

        if (isset($dataItems['pagination']) && is_array($dataItems['pagination'])) {
            $dataItems['pagination']['totalCount'] = count($filteredPayments);
        }

        return $dataItems;
    }
}
