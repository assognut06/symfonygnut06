<?php
// src/Controller/ContactController.php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use GuzzleHttp\Client;

class ContactController extends AbstractController
{
    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, ValidatorInterface $validator): Response
    {
        $messageEnvoye = false;
        $errors = [];

        if ($request->isMethod('POST')) {
            // Récupérer les données du formulaire
            $firstName = $request->request->get('first_name');
            $lastName = $request->request->get('last_name');
            $email = $request->request->get('email');
            $tel = $request->request->get('tel');
            $message = $request->request->get('message');
            $recaptchaResponse = $request->request->get('g-recaptcha-response');

            // Validation de l'adresse email
            $emailConstraint = new EmailConstraint();
            $emailConstraint->message = 'Cette adresse email n\'est pas valide.';
            $validationErrors = $validator->validate($email, $emailConstraint);

            // Vérifier s'il y a des erreurs de validation
            if (count($validationErrors) > 0) {
                // Récupérer les messages d'erreur
                foreach ($validationErrors as $error) {
                    $errors[] = $error->getMessage();
                }
            }

            // Vérifier la réponse reCAPTCHA
            $client = new Client();
            $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => $_ENV['NOCAPTCHA_SECRET'],
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp()
                ]
            ]);

            $responseData = json_decode($response->getBody());

            if (!$responseData->success || $responseData->score < 0.5) {
                $errors[] = 'La vérification reCAPTCHA a échoué. Veuillez réessayer.';
            }

            if (empty($errors)) {
                // Construire le message
                $body = "Prénom : $firstName\nNom : $lastName\nEmail : $email\nTéléphone : $tel\nMessage : $message";
                $subject = 'Message du site Gnut06.org';
                $to = 'gnut@gnut06.org';
                $headers = 'From: ' . $email . "\r\n" .
                    'Reply-To: ' . $email . "\r\n" .
                    'Content-Type: text/plain; charset=UTF-8' . "\r\n" .
                    'Content-Transfer-Encoding: 8bit' . "\r\n" .
                    'X-Mailer: PHP/' . phpversion();

                // Envoyer l'email
                if (mail($to, $subject, $body, $headers)) {
                    $messageEnvoye = true;
                } else {
                    $errors[] = "L'envoi de l'email a échoué. Veuillez réessayer plus tard.";
                }
            }
        }

        return $this->render('contact/index.html.twig', [
            'message_envoye' => $messageEnvoye,
            'errors' => $errors,
            'site_key' => $_ENV['NOCAPTCHA_SITEKEY']
        ]);
    }
}