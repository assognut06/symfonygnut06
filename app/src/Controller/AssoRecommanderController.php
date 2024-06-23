<?php

namespace App\Controller;

use App\Form\AssoRecommanderType;
use App\Service\AssoRecommanderService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\PaginationService;
use App\Entity\AssoRecommander;
use App\Repository\AssoRecommanderRepository;
use App\Service\HelloAssoApiService; // Service dédié pour les appels API HelloAsso
use DateTime;

class AssoRecommanderController extends AbstractController
{
    private $assoRecommanderService;
    private $assoRecommanderRepository;
    private $helloAssoApiService;

    function __construct(AssoRecommanderService $assoRecommanderService, AssoRecommanderRepository $assoRecommanderRepository, HelloAssoApiService $helloAssoApiService)
    {
        $this->assoRecommanderService = $assoRecommanderService;
        $this->assoRecommanderRepository = $assoRecommanderRepository;
        $this->helloAssoApiService = $helloAssoApiService;
    }

    #[Route('/asso/recommander/{page}', name: 'app_asso_recommander', defaults: ['page' => 1])]
    public function index(PaginationService $paginationService, int $page = 1): Response
    {
        $pagination = $paginationService->getPaginatedData(AssoRecommander::class, $page);
        return $this->render('asso_recommander/index.html.twig', [
            'controller_name' => 'AssoRecommanderController',
            'data_forms' => $pagination['data'],
            'total' => $pagination['total'],
            'pages' => $pagination['pages'],
            'page' => $pagination['current_page'],
        ]);
    }

    #[Route('/asso/recommander/{organizationSlug}/{formTypes}/{page}', name: 'app_asso_evenements', defaults: ['page' => 1, 'formTypes' => 'Event'])]
    public function evenementsAssoRecommander(string $organizationSlug,int $page, string $formTypes): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}/forms?states=&formTypes={$formTypes}";

        $data_forms = $this->helloAssoApiService->makeApiCall($url);

        // Supposons que $data_forms['data'] contient les données que vous avez mentionnées
        $filteredData = array_filter($data_forms['data'], function ($entry) {

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

        $itemsPerPage = 6;
        $totalItems = count($filteredData);
        $totalPages = ceil($totalItems / $itemsPerPage);

        // Calculer l'index de départ pour la page actuelle
        $start = ($page - 1) * $itemsPerPage;
        
        // Extraire les éléments pour la page actuelle
        $pageItems = array_slice($filteredData, $start, $itemsPerPage);
        
        return $this->render('asso_recommander/events.html.twig', [
            'controller_name' => 'AssoRecommanderController',
            'data_forms' => $pageItems,
            'total' => $totalItems,
            'pages' => $totalPages,
            'page' => $page,
        ]);
    }

    #[Route('/admin/asso/recommander/new', name: 'app_asso_recommander_new')]
    public function new(Request $request): Response
    {
        // $assoRecommander = new AssoRecommander();
        $form = $this->createForm(AssoRecommanderType::class);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $organizationSlug = $form->get('organizationSlug')->getData();

            // Vérifier si l'organizationSlug existe déjà
            if ($this->assoRecommanderRepository->existsByOrganizationSlug($organizationSlug)) {
                // Ajouter un message flash pour informer l'utilisateur
                $this->addFlash('danger', 'Le slug de l\'organisation existe déjà.');
                return $this->redirectToRoute('app_asso_recommander_new');
            } else {

                $data = $this->assoRecommanderService->updateAssoRecommanderFromApi($organizationSlug);

                if ($data) {
                    $this->addFlash('success', 'L\'association a bien été recommandée !');
                } else {
                    $this->addFlash('danger', 'L\'association n\'a pas été trouvée !');
                }

                return $this->redirectToRoute('app_asso_recommander_new');
            }
        }

        return $this->render('asso_recommander/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
