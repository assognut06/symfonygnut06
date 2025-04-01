<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SitemapController extends AbstractController
{
    #[Route('/sitemap', name: 'sitemap', defaults: ['_format' => 'xml'])]
    public function index(): Response
    {
        // Liste des URLs à inclure dans le sitemap
        $urls = [
            ['loc' => $this->generateUrl('app_home', [], true), 'priority' => '1.0'],
            ['loc' => $this->generateUrl('app_adhesion', [], true), 'priority' => '0.9'],
            ['loc' => $this->generateUrl('app_don', [], true), 'priority' => '0.9'],
            ['loc' => $this->generateUrl('app_metavers', [], true), 'priority' => '0.8'],
            ['loc' => $this->generateUrl('app_agent_handicap', [], true), 'priority' => '0.8'],
            ['loc' => $this->generateUrl('app_contact', [], true), 'priority' => '0.8'],
            ['loc' => $this->generateUrl('app_a_propos', [], true), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_presse', [], true), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_billetteries', [], true), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_asso_recommander', [], true), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_mention_legales', [], true), 'priority' => '0.2'],
            ['loc' => $this->generateUrl('app_login', [], true), 'priority' => '0.8'],

            // Ajoutez d'autres URLs ici
        ];

        // // Générer la réponse XML
        // $response = new Response(
        //     $this->renderView('sitemap/sitemap.html.twig', ['urls' => $urls]),
        //     Response::HTTP_OK,
        //     ['Content-Type' => 'application/xml']
        // );

        // return $response;
        return $this->render('sitemap/sitemap.xml.Twig', [
            'urls' => $urls,
            'Content-Type' => 'application/xml'
        ]);
    }
}