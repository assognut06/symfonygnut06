<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class SitemapController extends AbstractController
{
    #[Route('/sitemap.xml', name: 'sitemap', defaults: ['_format' => 'xml'])]
    public function index(): Response
    {
         // Liste des URLs à inclure dans le sitemap
         $urls = [
            ['loc' => $this->generateUrl('app_home', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '1.0'],
            ['loc' => $this->generateUrl('app_adhesion', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.9'],
            ['loc' => $this->generateUrl('app_don', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.9'],
            ['loc' => $this->generateUrl('app_metavers', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.8'],
            ['loc' => $this->generateUrl('app_agent_handicap', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.8'],
            ['loc' => $this->generateUrl('app_contact', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.8'],
            ['loc' => $this->generateUrl('app_a_propos', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_presse', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_billetteries', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_asso_recommander', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.7'],
            ['loc' => $this->generateUrl('app_mention_legales', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.2'],
            ['loc' => $this->generateUrl('app_login', [], UrlGeneratorInterface::ABSOLUTE_URL), 'priority' => '0.8'],
            

            // Ajoutez d'autres URLs ici
        ];

        // // Générer la réponse XML
        // $response = new Response(
        //     $this->renderView('sitemap/sitemap.html.twig', ['urls' => $urls]),
        //     Response::HTTP_OK,
        //     ['Content-Type' => 'application/xml']
        // );

        // return $response;
        return new Response(
            $this->renderView('sitemap/sitemap.xml.twig', ['urls' => $urls]),
            Response::HTTP_OK,
            ['Content-Type' => 'application/xml']
        );
    }
}