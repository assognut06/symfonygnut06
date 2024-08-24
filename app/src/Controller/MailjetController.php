<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use \Mailjet\Resources;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class MailjetController extends AbstractController
{
    #[Route('/mailjet', name: 'app_mailjet')]
    public function index()
    {
        $apiKey = $_ENV['APIMAILJET'];
        $apiSecret = $_ENV['APIMAILJETSECRET'];

        // Utilisez vos clés API ici
        $mj = new \Mailjet\Client($apiKey, $apiSecret, true, ['version' => 'v3.1']);
        $body = [
            'Messages' => [
                [
                    'From' => [
                        'Email' => "gnut@gnut06.org",
                        'Name' => "Gérald COL"
                    ],
                    'To' => [
                        [
                            'Email' => "gnut@gnut06.org",
                            'Name' => "Gérald COL"
                        ]
                    ],
                    'Subject' => "Greetings from Mailjet.",
                    'TextPart' => "My first Mailjet email",
                    'HTMLPart' => "<h3>Dear passenger 1, welcome to <a href='https://www.mailjet.com/'>Mailjet</a>!</h3><br />May the delivery force be with you!",
                    'CustomID' => "AppGettingStartedTest"
                ]
            ]
        ];
        $response = $mj->post(Resources::$Email, ['body' => $body]);
        $response->success() && var_dump($response->getData());
        return new Response('Email sent successfully');
    }
}
