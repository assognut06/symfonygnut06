<?php

namespace App\Controller;

use App\Service\OhmeApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AdminOhmeController extends AbstractController
{
    private $ohmeApiService;

    public function __construct(OhmeApiService $ohmeApiService)
    {
        $this->ohmeApiService = $ohmeApiService;
    }

    #[Route('/admin/ohme', name: 'app_admin_ohme')]
    public function index(): Response
    {
        $params = [
            'limit' => 100,
            'segment_id' => '2251799858000067',
        ];
        $object = 'contacts';

        $contacts = $this->ohmeApiService->getContacts($params, $object);

        return $this->render('admin_ohme/index.html.twig', [
            'controller_name' => 'AdminOhmeController',
            'contacts' => $contacts,
        ]);
    }
}
