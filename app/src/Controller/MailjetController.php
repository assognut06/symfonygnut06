<?php

namespace App\Controller;

use App\Service\MailjetService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class MailjetController extends AbstractController
{
    private $mailjetService;

    public function __construct(MailjetService $mailjetService)
    {
        $this->mailjetService = $mailjetService;
    }

    #[Route('/mailjet', name: 'app_mailjet')]
    public function index()
    {
        $message = [
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
        ];

        $response = $this->mailjetService->sendEmail($message);
        if ($response) {
            var_dump($response);
        }

        return new Response('Email sent successfully');
    }
}
