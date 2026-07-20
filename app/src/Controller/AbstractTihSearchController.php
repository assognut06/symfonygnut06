<?php

namespace App\Controller;

use App\Application\Command\CommandBus;
use App\Application\Command\Tih\SendTihContactCommand;
use App\Application\DTO\Tih\TihContactDTO;
use App\Application\DTO\Tih\TihMapProfileDTO;
use App\Application\Query\QueryBus;
use App\Application\Query\Tih\GetAvailableFiltersQuery;
use App\Application\Query\Tih\SearchTihQuery;
use App\Application\ViewModel\Tih\TihContactViewModel;
use App\Application\ViewModel\Tih\TihDetailsViewModel;
use App\Entity\Tih;
use App\Form\TihContactType;
use App\Repository\TihRepository;
use App\Service\GeocodeService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

abstract class AbstractTihSearchController extends AbstractController
{
    private const ITEMS_PER_PAGE = 12;

    public function __construct(
        protected QueryBus $queryBus,
        protected CommandBus $commandBus,
        protected GeocodeService $geocodeService,
        protected string $googleMapsApiKey,
    ) {}

    /**
     * @return array<string, mixed>
     */
    protected function getSearchViewData(Request $request, string $detailRoute): array
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $queryParams = $request->query->all();
        $filters = [
            'skills' => array_filter(array_map('intval', (array) ($queryParams['skills'] ?? []))),
            'regions' => array_filter((array) ($queryParams['regions'] ?? [])),
            'departements' => array_filter((array) ($queryParams['departements'] ?? [])),
            'availability' => array_filter((array) ($queryParams['availability'] ?? [])),
        ];

        if (isset($queryParams['minRate']) && $queryParams['minRate'] !== '') {
            $filters['minRate'] = (float) $queryParams['minRate'];
        }

        if (isset($queryParams['maxRate']) && $queryParams['maxRate'] !== '') {
            $filters['maxRate'] = (float) $queryParams['maxRate'];
        }

        if (isset($queryParams['rateType']) && $queryParams['rateType'] !== '' && $queryParams['rateType'] !== 'all') {
            $filters['rateType'] = $queryParams['rateType'];
        }

        if (isset($queryParams['availabilityPeriod']) && $queryParams['availabilityPeriod'] !== '') {
            $period = $queryParams['availabilityPeriod'];
            $now = new \DateTime();

            if ($period === '1') {
                $filters['availabilityDate'] = (clone $now)->modify('+1 month');
            } elseif ($period === '3') {
                $filters['availabilityDate'] = (clone $now)->modify('+3 months');
            } elseif ($period === '3+') {
                $filters['availabilityDateAfter'] = (clone $now)->modify('+3 months');
            }

            $filters['availabilityPeriod'] = $period;
        }

        $paginator = $this->queryBus->ask(
            new SearchTihQuery($filters, $page, self::ITEMS_PER_PAGE)
        );

        $totalItems = count($paginator);
        $totalPages = (int) ceil($totalItems / self::ITEMS_PER_PAGE);

        $availableFilters = $this->queryBus->ask(
            new GetAvailableFiltersQuery($filters)
        );

        $allFilteredResults = $this->queryBus->ask(
            new SearchTihQuery($filters, 1, 10000)
        );

        $cities = [];
        foreach ($allFilteredResults as $tih) {
            $city = $tih->getCity();
            if (!empty($city) && !in_array($city, $cities, true)) {
                $cities[] = $city;
            }
        }

        return [
            'tihs' => $paginator,
            'allTihs' => $this->buildMapProfiles($allFilteredResults, $detailRoute),
            'currentFilters' => $filters,
            'availableFilters' => $availableFilters,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'totalItems' => $totalItems,
            'itemsPerPage' => self::ITEMS_PER_PAGE,
            'cityCoordinates' => $this->geocodeService->getCitiesCoordinates($cities),
            'googleMapsApiKey' => $this->googleMapsApiKey,
        ];
    }

    protected function getTihDetailsViewModel(TihRepository $tihRepository, int $id): TihDetailsViewModel
    {
        return TihDetailsViewModel::fromEntity($this->getValidatedTih($tihRepository, $id));
    }

    protected function createPublicCvResponse(TihRepository $tihRepository, int $id): BinaryFileResponse
    {
        $tih = $this->getValidatedTih($tihRepository, $id);

        if (!$tih->getCv()) {
            throw $this->createNotFoundException('CV introuvable.');
        }

        return $this->createInlineFileResponse(
            (string) $tih->getCv(),
            (string) $this->getParameter('cv_tih_directory'),
            'public/uploads/tihcv',
            'CV introuvable.'
        );
    }

    protected function createPublicAttestationResponse(TihRepository $tihRepository, int $id): BinaryFileResponse
    {
        $tih = $this->getValidatedTih($tihRepository, $id);

        if (!$tih->getAttestationTih()) {
            throw $this->createNotFoundException('Attestation introuvable.');
        }

        return $this->createInlineFileResponse(
            (string) $tih->getAttestationTih(),
            (string) $this->getParameter('attestation_tih_directory'),
            'public/uploads/tihattest',
            'Attestation introuvable.'
        );
    }

    /**
     * @return array<string, mixed>|Response
     */
    protected function handleContactRequest(Request $request, TihRepository $tihRepository, int $id, string $detailsRoute): array|Response
    {
        $tih = $this->getValidatedTih($tihRepository, $id);
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

                return $this->redirectToRoute($detailsRoute, ['id' => $id]);
            } catch (\Exception $e) {
                $this->addFlash('error', 'Une erreur est survenue lors de l\'envoi du message. Veuillez réessayer.');
            }
        }

        return [
            'form' => $form->createView(),
            'tih' => $tihViewModel,
        ];
    }

    /**
     * @param iterable<Tih> $tihs
     *
     * @return TihMapProfileDTO[]
     */
    private function buildMapProfiles(iterable $tihs, string $detailRoute): array
    {
        $profiles = [];

        foreach ($tihs as $tih) {
            $profiles[] = TihMapProfileDTO::fromEntity(
                $tih,
                $this->generateUrl($detailRoute, ['id' => $tih->getId()])
            );
        }

        return $profiles;
    }

    private function getValidatedTih(TihRepository $tihRepository, int $id): Tih
    {
        $tih = $tihRepository->findValidatedById($id);

        if (!$tih) {
            throw $this->createNotFoundException('TIH non trouvé.');
        }

        return $tih;
    }

    private function createInlineFileResponse(
        string $storedFilename,
        string $uploadDirectory,
        string $legacyDirectory,
        string $notFoundMessage
    ): BinaryFileResponse {
        $fileName = basename($storedFilename);
        $filePath = rtrim($uploadDirectory, '/') . '/' . $fileName;

        if (!is_file($filePath)) {
            $legacyPath = rtrim((string) $this->getParameter('kernel.project_dir'), '/') . '/' . trim($legacyDirectory, '/') . '/' . $fileName;

            if (is_file($legacyPath)) {
                $filePath = $legacyPath;
            } else {
                throw $this->createNotFoundException($notFoundMessage);
            }
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);

        return $response;
    }
}
