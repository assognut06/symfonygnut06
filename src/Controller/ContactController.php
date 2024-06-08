<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints\Email as EmailConstraint;
use Symfony\Component\Validator\Validator\ValidatorInterface;

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

            if (empty($errors)) {
                // Construire le message
                $body = "Prénom : $firstName\nNom : $lastName\nEmail : $email\nTéléphone : $tel\nMessage : $message";
                $subject = 'Message du site Gnut06.org';
                $to = 'gnut@gnut.eu';
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
            'currentDate' => new \DateTime()
        ]);
    }
}