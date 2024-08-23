<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use \Mailjet\Resources;

class MailjetController extends AbstractController
{
    #[Route('/mailjet', name: 'app_mailjet')]
    public function index()
    {
        $mj = new \Mailjet\Client('****************************1234','****************************abcd',true,['version' => 'v3.1']);
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

    }
}
