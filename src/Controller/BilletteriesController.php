<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class BilletteriesController extends AbstractController
{
    #[Route('/billetteries', name: 'app_billetteries')]
    public function index(Request $request, SessionInterface $session): Response
    {
        $session->set('my_key', 'my_value');
        $bearer_token = "eyJhbGciOiJSUzI1NiIsInR5cCI6IkpXVCJ9.eyJqdGkiOiI0YWU4Y2JmMGEwMTc0MzAyMmQwNjA4ZGM4ODQxNTg1MiIsInVycyI6Ik9yZ2FuaXphdGlvbkFkbWluIiwiY3BzIjpbIkFjY2Vzc1B1YmxpY0RhdGEiLCJBY2Nlc3NUcmFuc2FjdGlvbnMiLCJDaGVja291dCJdLCJuYmYiOjE3MTc5NTAyNTYsImV4cCI6MTcxNzk1MjA1NiwiaXNzIjoiaHR0cHM6Ly9hcGkuaGVsbG9hc3NvLmNvbSIsImF1ZCI6IjZiZWJiYTU5ZWZhMTQ0Njk4ZWFhYzc4NGY0ZGUxZmNmIn0.cBaBR4zJuN79f45Sf_-c5ptGDTpXjv-ALFx2eur7EVuWDBI7ea3QQBHaTxKHsFi2lgryKNBDmjLy2mHIpnq9FUpXz1I7y8pvV8CP0KX6dNjOXMHLLtS9lyILBj9wixO2Kx9C8sOFAHh4E_UDoLcsIwlB7TvYU_oijDqKh1Io15NtPCvKxxlpH5O6bYJKB8Fqw1Cld8j6LXXUPOh79JXFDgoKukc6njD8mXGcIzDllWLBQdWUQAOExCSXAP03AVWNQay3dKaEnwuwbxKywyEZKa_zB51k1In5SkIsv3WLQQ8a3lOWMdyUx6DNNlIWtcu7Rm041LUWeTLICNac3aF7rQ";
        $bearerToken = $session->get('bearer_token');

        // Dans votre méthode de contrôleur
        $allSessionData = $session->all();
        dump($allSessionData);
        dump($bearerToken); // Utilisez dump() pour Symfony ou error_log() pour un log simple
        dump($bearer_token);
        exit;
        return $this->render('billetteries/index.html.twig', [
            'controller_name' => 'BilletteriesController',
            'currentDate' => new \DateTime(),
            'bearer_token' => $bearerToken // Passer le token à la vue
        ]);
    }
}
