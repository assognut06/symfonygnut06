<?php
// src/Controller/EmailController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class EmailController extends AbstractController
{
    // #[Route('/email', name: 'send_email')]
    public function sendEmail(MailerInterface $mailer): Response
    {
        $email = (new Email())
            ->from('gnut@gnut.eu') // Remplacez par votre adresse email d'envoi
            ->to('gnut@gnut.eu') // Remplacez par l'adresse email du destinataire
            ->subject('Test d\'envoi d\'email avec Symfony Mailer')
            ->text('Ceci est un test d\'envoi d\'email.')
            ->html('<p>Ceci est un test d\'envoi d\'email.</p>');

        var_dump($email);
        $mailer->send($email);

        return new Response("success", 200);
    }
}