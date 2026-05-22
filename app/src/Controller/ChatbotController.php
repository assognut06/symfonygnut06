<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

final class ChatbotController extends AbstractController
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,

        #[Autowire('%env(OPENAI_API_KEY)%')]
        private readonly string $openAiApiKey,

        #[Autowire('%env(OPENAI_MODEL)%')]
        private readonly string $openAiModel,
    ) {
    }

    #[Route('/api/chatbot/ask', name: 'chatbot_ask', methods: ['POST'])]
    public function ask(Request $request): JsonResponse
    {
        $csrfToken = $request->headers->get('X-CSRF-TOKEN', '');

        if (!$this->isCsrfTokenValid('chatbot_ask', $csrfToken)) {
            return $this->json([
                'error' => 'Jeton de sécurité invalide.',
            ], 403);
        }

        $payload = json_decode($request->getContent(), true);

        if (!is_array($payload)) {
            return $this->json([
                'error' => 'Requête invalide.',
            ], 400);
        }

        $message = trim((string) ($payload['message'] ?? ''));

        if ($message === '') {
            return $this->json([
                'error' => 'Le message est vide.',
            ], 400);
        }

        if (mb_strlen($message) > 1500) {
            return $this->json([
                'error' => 'Le message est trop long.',
            ], 400);
        }

        $history = $this->cleanHistory($payload['history'] ?? []);

        $input = $history;
        $input[] = [
            'role' => 'user',
            'content' => $message,
        ];

        $instructions = <<<TXT
Tu es l'Agent IA de gnut06.org, un assistant d'information pour les personnes en situation de handicap.

Objectif :
- Répondre en français simple.
- Aider sur les démarches handicap : MDPH, AAH, RQTH, CMI, emploi, logement, transport, accessibilité, droits, accompagnement.
- Donner des étapes concrètes et faciles à suivre.
- Être bienveillant, clair et rassurant.

Règles importantes :
- Tu ne remplaces pas un médecin, un avocat, une assistante sociale ou un service d'urgence.
- Tu ne poses pas de diagnostic médical.
- Tu ne demandes pas d'informations sensibles inutiles.
- Si la personne semble en danger immédiat, conseille d'appeler le 112, le 15, le 17 ou le 18 selon la situation.
- Si tu n'es pas sûr, dis de vérifier auprès d'un organisme officiel : MDPH, CAF, France Services, mairie, CCAS, médecin, travailleur social.
- Pour les réponses administratives, donne une réponse pratique mais rappelle que les règles peuvent dépendre de la situation personnelle.
- Si la personne demande une réponse très simple, réponds en langage encore plus clair, proche du FALC.
TXT;

        try {
            $response = $this->httpClient->request('POST', 'https://api.openai.com/v1/responses', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->openAiApiKey,
                ],
                'json' => [
                    'model' => $this->openAiModel,
                    'instructions' => $instructions,
                    'input' => $input,
                    'max_output_tokens' => 600,
                    'store' => false,
                ],
                'timeout' => 30,
            ]);

            $data = $response->toArray(false);

            if (isset($data['error'])) {
                return $this->json([
                    'error' => 'Erreur OpenAI.',
                    'details' => $data['error']['message'] ?? null,
                ], 500);
            }

            $reply = $this->extractOutputText($data);

            if ($reply === '') {
                $reply = "Je suis désolé, je n’ai pas réussi à générer une réponse. Vous pouvez reformuler votre question.";
            }

            return $this->json([
                'reply' => $reply,
            ]);
        } catch (Throwable $exception) {
            return $this->json([
                'error' => 'Le chatbot est momentanément indisponible.',
            ], 500);
        }
    }

    private function cleanHistory(mixed $history): array
    {
        if (!is_array($history)) {
            return [];
        }

        $clean = [];

        foreach (array_slice($history, -8) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $role = $item['role'] ?? '';
            $content = trim((string) ($item['content'] ?? ''));

            if (!in_array($role, ['user', 'assistant'], true)) {
                continue;
            }

            if ($content === '') {
                continue;
            }

            $clean[] = [
                'role' => $role,
                'content' => mb_substr($content, 0, 1000),
            ];
        }

        return $clean;
    }

    private function extractOutputText(array $data): string
    {
        if (isset($data['output_text']) && is_string($data['output_text'])) {
            return trim($data['output_text']);
        }

        $texts = [];

        foreach (($data['output'] ?? []) as $outputItem) {
            if (!is_array($outputItem)) {
                continue;
            }

            foreach (($outputItem['content'] ?? []) as $contentItem) {
                if (!is_array($contentItem)) {
                    continue;
                }

                if (isset($contentItem['text']) && is_string($contentItem['text'])) {
                    $texts[] = $contentItem['text'];
                }
            }
        }

        return trim(implode("\n", $texts));
    }
}