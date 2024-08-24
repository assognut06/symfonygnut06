<?php

namespace App\Service;

use Mailjet\Client;
use Mailjet\Resources;

class MailjetService
{
    private $apiKey;
    private $apiSecret;

    public function __construct(string $apiKey, string $apiSecret)
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
    }

    public function sendEmail(array $message)
    {
        $mj = new Client($this->apiKey, $this->apiSecret, true, ['version' => 'v3.1']);
        $body = [
            'Messages' => [$message]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        return $response->success() ? $response->getData() : null;
    }
}