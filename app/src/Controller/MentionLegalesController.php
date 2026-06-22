<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MentionLegalesController extends AbstractController
{
    #[Route('/mention/legales', name: 'app_mention_legales_legacy')]
    public function legacy(): RedirectResponse
    {
        return $this->redirectToRoute('app_mentions_legales', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/mentions-legales', name: 'app_mentions_legales')]
    public function mentionsLegales(): Response
    {
        return $this->render('mention_legales/index.html.twig', [
            'page' => 'mentions_legales',
            'title' => 'Mentions légales | GNUT 06',
            'description' => 'Informations légales de l’association GNUT 06, éditrice du site gnut06.org.',
        ]);
    }

    #[Route('/politique-confidentialite', name: 'app_politique_confidentialite')]
    public function politiqueConfidentialite(): Response
    {
        return $this->render('mention_legales/index.html.twig', [
            'page' => 'politique_confidentialite',
            'title' => 'Politique de confidentialité | GNUT 06',
            'description' => 'Informations sur la collecte, l’utilisation et la conservation des données personnelles par GNUT 06.',
        ]);
    }

    #[Route('/politique-cookies', name: 'app_politique_cookies')]
    public function politiqueCookies(): Response
    {
        return $this->render('mention_legales/index.html.twig', [
            'page' => 'politique_cookies',
            'title' => 'Politique cookies | GNUT 06',
            'description' => 'Informations sur l’utilisation des cookies et traceurs sur le site gnut06.org.',
        ]);
    }

    #[Route('/charte-chatbot-ia', name: 'app_charte_chatbot_ia')]
    public function charteChatbotIa(): Response
    {
        return $this->render('mention_legales/index.html.twig', [
            'page' => 'charte_chatbot_ia',
            'title' => 'Charte chatbot IA | GNUT 06',
            'description' => 'Informations sur l’utilisation du chatbot IA proposé sur le site gnut06.org.',
        ]);
    }
}
