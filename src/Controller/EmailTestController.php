<?php
// src/Controller/EmailTestController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailTestController extends AbstractController
{
    #[Route('/send-test-email', name: 'send_test_email')]
    public function sendTestEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('gnut@gnut.eu')
            ->to('gnut@gnut.eu')
            ->subject('Test Email from Symfony')
            ->text('This is a test email sent from Symfony using Gmail.');

        try {
            $to = "gnut@gnut.eu";
            $subject = "Test mail PHP";
            $content = "The body/content of the Email";
            $headers = "From: Website <SendingEmail@address.tld>\r\nReply-To: SendingEmail@address.tld";

            if (mail($to, $subject, $content, $headers))
                echo "The email has been sent successfully!";
            else
                echo "Email did not leave correctly!";
            $mailer->send($email);
            return new Response('Email sent successfully!');
        } catch (\Exception $e) {
            return new Response('Failed to send email: ' . $e->getMessage());
        }
    }
}
