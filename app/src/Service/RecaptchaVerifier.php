<?php
// src/Service/RecaptchaVerifier.php
namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaVerifier
{
    private const EXPECTED_ACTION = 'submit';
    private const MINIMUM_SCORE = 0.5;

    public function __construct(
        private HttpClientInterface $client,
        private string $appEnv,
        private string $recaptchaSecret,
    ) {}

    public function verify(Request $request): bool
    {
        if ($this->appEnv === 'dev') {
            return true;
        }

        $recaptchaResponse = $request->request->get('g-recaptcha-response');

        if (empty($recaptchaResponse)) {
            return false;
        }

        try {
            $response = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'body' => [
                    'secret' => $this->recaptchaSecret,
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp()
                ]
            ]);

            $data = json_decode($response->getContent(), true);
        } catch (ExceptionInterface) {
            return false;
        }

        if (!is_array($data)) {
            return false;
        }

        $score = $data['score'] ?? null;

        return ($data['success'] ?? false) === true
            && ($data['action'] ?? null) === self::EXPECTED_ACTION
            && is_numeric($score)
            && (float) $score >= self::MINIMUM_SCORE;
    }
}
