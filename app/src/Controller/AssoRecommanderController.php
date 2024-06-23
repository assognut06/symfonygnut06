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

class AssoRecommanderController extends AbstractController
{
    private $assoRecommanderService;

    function __construct(AssoRecommanderService $assoRecommanderService)
    {
        $this->assoRecommanderService = $assoRecommanderService;
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

    #[Route('/admin/asso/recommander/new', name: 'app_asso_recommander_new')]
    public function new(Request $request): Response
    {
        // $assoRecommander = new AssoRecommander();
        $form = $this->createForm(AssoRecommanderType::class);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $organizationSlug = $form->get('organizationSlug')->getData();
            $this->assoRecommanderService->updateAssoRecommanderFromApi($organizationSlug);
            // $entityManager->persist($assoRecommander);
            // $entityManager->flush();

            $this->addFlash('success', 'L\'association a bien été recommandée !');
    
            return $this->redirectToRoute('app_asso_recommander_new');
        }
    
        return $this->render('asso_recommander/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
