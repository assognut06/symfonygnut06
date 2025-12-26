<?php

namespace App\Controller;

use App\Application\Command\CommandBus;
use App\Application\Command\Tih\SendTihContactCommand;
use App\Application\Query\QueryBus;
use App\Application\Query\Tih\GetAvailableFiltersQuery;
use App\Application\Query\Tih\SearchTihQuery;
use App\Application\DTO\Tih\TihContactDTO;
use App\Application\ViewModel\Tih\TihContactViewModel;
use App\Application\ViewModel\Tih\TihDetailsViewModel;
use App\Entity\Tih;
use App\Form\TihContactType;
use App\Repository\TihRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

#[Route('/tih')]
class TihSearchController extends AbstractController
{
    private const ITEMS_PER_PAGE = 12;

    public function __construct(
        private QueryBus $queryBus,
        private CommandBus $commandBus
    ) {}

    #[Route('/tih_search', name: 'app_tih_search', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));

        // Get filters from request
        $queryParams = $request->query->all();
        $filters = [
            'skills' => array_filter(array_map('intval', (array) ($queryParams['skills'] ?? []))),
            'cities' => array_filter((array) ($queryParams['cities'] ?? [])),
            'availability' => array_filter((array) ($queryParams['availability'] ?? [])),
        ];

        // Execute query to get paginated results with filters
        $paginator = $this->queryBus->ask(
            new SearchTihQuery($filters, $page, self::ITEMS_PER_PAGE)
        );
        
        // Calculate pagination data
        $totalItems = count($paginator);
        $totalPages = (int) ceil($totalItems / self::ITEMS_PER_PAGE);

        // Execute query to get available filters based on current selection
        $availableFilters = $this->queryBus->ask(
            new GetAvailableFiltersQuery($filters)
        );

        return $this->render('tih_search/index.html.twig', [
            'tihs' => $paginator,
            'currentFilters' => $filters,
            'availableFilters' => $availableFilters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => self::ITEMS_PER_PAGE,
        ]);
    }

    #[Route('/tih/{id}', name: 'app_tih_details', methods: ['GET'])]
    public function details(TihRepository $tihRepository, int $id): Response
    {
        $tih = $tihRepository->find($id);

        if (!$tih) {
            throw $this->createNotFoundException('TIH non trouvé.');
        }

        return $this->render('tih_search/details.html.twig', [
            'tih' => TihDetailsViewModel::fromEntity($tih),
        ]);
    }

    #[Route('/tih/{id}/contact', name: 'app_tih_contact', methods: ['GET', 'POST'])]
    public function contact(Request $request, TihRepository $tihRepository, int $id): Response
    {
        $tih = $tihRepository->find($id);

        if (!$tih) {
            throw $this->createNotFoundException('TIH non trouvé.');
        }

        $tihViewModel = TihContactViewModel::fromEntity($tih);
        $form = $this->createForm(TihContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var TihContactDTO $contactData */
            $contactData = $form->getData();

            try {
                $this->commandBus->dispatch(
                    new SendTihContactCommand($id, $contactData)
                );
                
                $this->addFlash('success', sprintf(
                    'Votre message a été envoyé avec succès à %s.',
                    $tihViewModel->fullName
                ));
                
                return $this->redirectToRoute('app_tih_details', ['id' => $id]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.');
                // Let the form re-render with the error message
            }
        }

        return $this->render('tih_search/contact.html.twig', [
            'form' => $form,
            'tih' => $tihViewModel,
        ]);
    }
}
