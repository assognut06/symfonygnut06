<?php

// src/Controller/ContactController.php

namespace App\Controller;

use App\Form\ContactType;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use GuzzleHttp\Client;

class ContactController extends AbstractController
{
    public function __construct(
        private string $nocaptchaSecret,
        private string $nocaptchaSiteKey,
        private string $appEnv,
        private string $fromEmail,
        private string $adminEmail,
    ) {}

    #[Route('/contact', name: 'app_contact')]
    public function contact(Request $request, MailerInterface $mailer): Response
    {
        $messageEnvoye = false;
        $errors = [];
        $form = $this->createForm(ContactType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            if (!$form->isValid()) {
                $errors = array_merge($errors, $this->getFormErrorMessages($form));
            }

            if (empty($errors)) {
                $recaptchaError = $this->verifyRecaptcha($request);

                if ($recaptchaError !== null) {
                    $errors[] = $recaptchaError;
                }
            }

            if (empty($errors)) {
                $contactData = $form->getData();
                $firstName = $contactData['first_name'];
                $lastName = $contactData['last_name'];
                $email = $contactData['email'];
                $tel = $contactData['tel'] ?? '';
                $projectType = $contactData['project_type'];
                $message = $contactData['message'];

                $body = "Prénom : $firstName\nNom : $lastName\nEmail : $email\nTéléphone : $tel\nType de projet : $projectType\nMessage : $message";

                $emailMessage = (new Email())
                    ->from($this->fromEmail)
                    ->replyTo($email)
                    ->to($this->adminEmail)
                    ->subject('Message du site Gnut06.org')
                    ->text($body);
 
                try {
                    $mailer->send($emailMessage);
                    $messageEnvoye = true;
                } catch (\Exception $e) {
                    $errors[] = "L'envoi de l'email a échoué. Veuillez réessayer plus tard.";
                }
            }
        }

        return $this->render('contact/index.html.twig', [
            'message_envoye' => $messageEnvoye,
            'errors' => $errors,
            'form' => $form->createView(),
            'site_key' => $this->nocaptchaSiteKey,
        ]);
    }

    /**
     * @return string[]
     */
    private function getFormErrorMessages(FormInterface $form): array
    {
        $messages = [];

        foreach ($form->getErrors(true) as $error) {
            $messages[] = $error->getMessage();
        }

        return $messages;
    }

    private function verifyRecaptcha(Request $request): ?string
    {
        if ($this->appEnv === 'dev') {
            return null;
        }

        $recaptchaResponse = trim((string) $request->request->get('g-recaptcha-response', ''));

        if ($this->nocaptchaSecret === '') {
            return 'La vérification anti-spam est temporairement indisponible.';
        }

        if ($recaptchaResponse === '') {
            return 'La vérification reCAPTCHA a échoué. Veuillez réessayer.';
        }

        try {
            $client = new Client(['timeout' => 5]);
            $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
                'form_params' => [
                    'secret' => $this->nocaptchaSecret,
                    'response' => $recaptchaResponse,
                    'remoteip' => $request->getClientIp(),
                ],
            ]);

            $responseData = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            return 'La vérification anti-spam est temporairement indisponible.';
        }

        if (
            !is_array($responseData)
            || !($responseData['success'] ?? false)
            || (float) ($responseData['score'] ?? 0) < 0.5
        ) {
            return 'La vérification reCAPTCHA a échoué. Veuillez réessayer.';
        }

        return null;
    }
}
