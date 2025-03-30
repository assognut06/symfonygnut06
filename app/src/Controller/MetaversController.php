<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\ApiFrameVrService;

#[Route('/metavers')]
class MetaversController extends AbstractController
{
    private $apiFrameVrService;

    public function __construct(ApiFrameVrService $apiFrameVrService)
    {
        $this->apiFrameVrService = $apiFrameVrService;
    }

    #[Route('/', name: 'app_metavers')]
    public function index(): Response
    {
        return $this->render('metavers/index.html.twig', [
            'controller_name' => 'MetaversController',
        ]);
    }

    #[Route('/frame/{idFrame}', name: 'app_metavers_ohme')]
    public function someEndpoint(string $idFrame): Response
    {
        $data = $this->apiFrameVrService->getSomeData($idFrame, 'frame');

        return $this->json($data);
    }

}
