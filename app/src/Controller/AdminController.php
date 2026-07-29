<?php
// src/Controller/AdminController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use App\Service\HelloAssoApiService; // Service dédié pour les appels API HelloAsso
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Http\Attribute\IsGranted;


#[Route('/admin')]
#[IsGranted('ROLE_ADMIN')]
class AdminController extends AbstractController
{

    private HelloAssoApiService $helloAssoApiService;
    private string $slugAsso;
    private string $googleMapsApiKey;

    public function __construct(
        HelloAssoApiService $helloAssoApiService,
        string $slugAsso,
        string $googleMapsApiKey
    )
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->slugAsso = $slugAsso;
        $this->googleMapsApiKey = $googleMapsApiKey;
    }
    #[Route('', name: 'admin_dashboard')]
    public function dashboard(): Response
    {
        $url = "https://api.helloasso.com/v5/organizations/{$this->slugAsso}";
        
        $data = $this->helloAssoApiService->makeApiCall($url);

        return $this->render('admin/dashbord/dashboard.html.twig', [
            'data' => $data,
            'loading' => false,
        ]);
    }

    #[Route('/{donnees}/{formType}/{formSlug}/{tierTypes}/{page}', name: 'admin_api', requirements: ['donnees' => 'orders|payments'], methods: ['GET'])]
    public function api(string $donnees, string $page, string $formType, string $formSlug, string $tierTypes): Response
    {
        $url = $this->buildApiUrl($donnees, $page, $formType, $formSlug, $tierTypes);


        $data_forms = $this->normalizeApiResponse(
            $this->helloAssoApiService->makeApiCall($url),
            $page
        );
        
        return $this->render('admin/orders/index.html.twig', [
            'data_forms' => $data_forms,
            'loading' => false,
        ]);
    }

    #[Route('/details/{donnees}/{id}', name: 'admin_details_show', requirements: ['donnees' => 'orders|payments'], methods: ['GET'])]
    public function details(string $donnees, string $id): Response
    {
        $url = $this->buildDetailsUrl($donnees, $id);

        $data_forms = $this->normalizeDetailsResponse(
            $this->helloAssoApiService->makeApiCall($url)
        );

        if($donnees === 'orders') {
            return $this->render('admin/orders/detailsOrder.html.twig', [
                'data_forms' => $data_forms,
                'googleMapsApiKey' => $this->googleMapsApiKey,
                'loading' => false,
            ]);
        }
        if($donnees === 'payments') {
            return $this->render('admin/orders/detailsPayment.html.twig', [
                'data_forms' => $data_forms,
                'googleMapsApiKey' => $this->googleMapsApiKey,
                'loading' => false,
            ]);
        }
        throw $this->createNotFoundException("Type de donnees non pris en charge: " . $donnees);
    }

    private function buildApiUrl(string $donnees, string $page, string $formType, string $formSlug, string $tierTypes): string {
        $baseUrl = "https://api.helloasso.com/v5/organizations/{$this->slugAsso}";
    
        switch ($donnees) {
            case 'orders':
                $url = $baseUrl . "/items?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true";
                if ($formType !== '1' && $formSlug !== '1') {
                    $url = $baseUrl . "/forms/" . $formType . "/" . $formSlug . "/items?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true";
                }
                if ($tierTypes !== '1') {
                    $url = $baseUrl . "/items?pageIndex=" . $page . "&pageSize=15&tierTypes=" . $tierTypes . "&withDetails=true&sortOrder=Desc&sortField=Date&itemStates=Processed&withCount=true";
                }
                break;
            case 'payments':
                $url = $baseUrl . "/payments?pageIndex=" . $page . "&pageSize=15&withDetails=true&sortOrder=Desc&sortField=Date&states=Authorized&withCount=true";
                if ($formType !== '1') {
                    $url = $baseUrl . "/payments/search?pageIndex=" . $page . "&pageSize=15&formType=" . $formType . "&sortOrder=Desc&sortField=Date&states=Authorized&withCount=true";
                }
                break;
            default:
                throw $this->createNotFoundException("Type de donnees non pris en charge: " . $donnees);
        }
    
        return $url;
    }    

    /**
     * @return array{data:array<mixed>,pagination:array{pageIndex:int,totalPages:int,totalCount:int}}
     */
    private function normalizeApiResponse(mixed $dataForms, string $page): array
    {
        if (!is_array($dataForms)) {
            $this->addFlash('danger', 'Impossible de recuperer les donnees HelloAsso pour le moment.');

            return $this->createEmptyApiResponse($page);
        }

        if (!isset($dataForms['data']) || !is_array($dataForms['data'])) {
            $dataForms['data'] = [];
        }

        if (!isset($dataForms['pagination']) || !is_array($dataForms['pagination'])) {
            $dataForms['pagination'] = [];
        }

        $dataForms['pagination'] = array_replace(
            $this->createEmptyPagination($page),
            $dataForms['pagination']
        );
        $dataForms['pagination']['pageIndex'] = max(1, (int) $dataForms['pagination']['pageIndex']);
        $dataForms['pagination']['totalPages'] = max(1, (int) $dataForms['pagination']['totalPages']);
        $dataForms['pagination']['totalCount'] = max(0, (int) $dataForms['pagination']['totalCount']);

        return $dataForms;
    }

    /**
     * @return array{data:array<mixed>,pagination:array{pageIndex:int,totalPages:int,totalCount:int}}
     */
    private function createEmptyApiResponse(string $page): array
    {
        return [
            'data' => [],
            'pagination' => $this->createEmptyPagination($page),
        ];
    }

    /**
     * @return array{pageIndex:int,totalPages:int,totalCount:int}
     */
    private function createEmptyPagination(string $page): array
    {
        return [
            'pageIndex' => max(1, (int) $page),
            'totalPages' => 1,
            'totalCount' => 0,
        ];
    }
    
    /**
     * @return ?array<mixed>
     */
    private function normalizeDetailsResponse(mixed $dataForms): ?array
    {
        if (!is_array($dataForms)) {
            $this->addFlash('danger', 'Impossible de recuperer le detail HelloAsso pour le moment.');

            return null;
        }

        return $dataForms;
    }

    private function buildDetailsUrl(string $type, string $id): string {
        $baseUrl = "https://api.helloasso.com/v5";
        switch ($type) {
            case 'orders':
                return $baseUrl . "/items/" . $id;
            case 'payments':
                return $baseUrl . "/payments/" . $id;
            default:
                throw $this->createNotFoundException("Type de donnees non pris en charge: " . $type);
        }
    }
}
