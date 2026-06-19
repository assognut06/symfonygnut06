<?php

namespace App\Service;

use Mailjet\Client;
use Mailjet\Resources;

class MailjetService
{
    private string $apiKey;
    private string $apiSecret;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

/**
 * @param array<mixed> $message
 */
    public function sendEmail(array $message): mixed
    {
        $mj = new Client($this->apiKey, $this->apiSecret, true, ['version' => 'v3.1']);
        $body = [
            'Messages' => [$message],
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);

        return $response->success() ? $response->getData() : null;
    }
}
