<?php
// src/Service/RecaptchaVerifier.php
namespace App\Service;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class RecaptchaVerifier
{
    public function __construct(
        private HttpClientInterface $client,
        private string $secretKey,
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

        $response = $this->client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'body' => [
                'secret' => $this->recaptchaSecret,
                'response' => $recaptchaResponse,
                'remoteip' => $request->getClientIp()
            ]
        ]);

        $data = json_decode($response->getContent(), true);

        return isset($data['success']) && $data['success'] === true;
    }
}
