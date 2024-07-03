<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Payers;
use Doctrine\ORM\EntityManagerInterface;

class NotificationController extends AbstractController
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    #[Route('/notification/callback', name: 'notification_callback', methods: ['POST'])]
    public function callback(Request $request, MailerInterface $mailer): Response
    {

        try{

            $expectedIp = '51.138.206.200';
            $clientIp = $request->getClientIp();
    
            if ($clientIp !== $expectedIp) {
                return new Response('Unauthorized', Response::HTTP_UNAUTHORIZED);
            }
    
            $content = $request->getContent();
            $data = json_decode($content, true);
    
            if ($data === null) {
                return new Response('Invalid JSON', Response::HTTP_BAD_REQUEST);
            }
    
            $data_payer = $data['data']['payer'];
    
            if ($data['eventType'] == 'Order') {
                $this->verifyPayer($data_payer);
                // Logique spécifique à 'Order', y compris l'envoi d'email si nécessaire
            } elseif ($data['eventType'] == 'Payment') {
                // Traitement des données pour un événement de type 'Payment'
            }
    
            // Logique d'envoi d'email (ajustez selon les besoins)
            $formattedData = print_r($data, true);
            $email = (new Email())
                ->from('gnut@gnut.eu')
                ->to('gnut@gnut.eu')
                ->subject('Notification Data')
                ->text('Voici les données de la notification : ' . $formattedData);
    
            $mailer->send($email);
    
            return new Response('OK');

        }  catch (\Exception $e) {
            return "Erreur callBack: " . $e;

        }
    }

    public function verifyPayer($data)
    {
        $payerEmail = $data['email'];

        // Recherchez l'entité Payer par email
        $payer = $this->em->getRepository(Payers::class)->findOneBy(['email' => $payerEmail]);

        try {
            if ($payer) {
                // Si le Payer existe, mettez à jour ses informations
                $this->updatePayer($payer, $data);
            } else {
                // Si le Payer n'existe pas, créez un nouvel enregistrement
                $payer = new Payers();
                $payer->setEmail($payerEmail);
                $payer->setCreatedAt(new \DateTimeImmutable());
                $this->updatePayer($payer, $data);
                $this->em->persist($payer);
            }

            // Enregistrez les modifications dans la base de données
            $this->em->flush();
        } catch (\Exception $e) {
            // Gérer l'exception, par exemple en loggant l'erreur
            return "Erreur verifyPlayer : " . $e;

        }
    }

    private function updatePayer(Payers $payer, array $data): void
    {
        // Définissez ou mettez à jour d'autres champs si nécessaire
        // Utilisez isset ou !empty selon le cas pour vérifier les données avant de les affecter
        (isset($data['address'])) ? $payer->setAddress($data['address']) : null;
        (isset($data['city'])) ? $payer->setCity($data['city']) : null;
        (isset($data['zipCode'])) ? $payer->setZipCode($data['zipCode']) : null;
        (isset($data['country'])) ? $payer->setCountry($data['country']) : null;
        (isset($data['company'])) ? $payer->setCompany($data['company']) : null;
        $payer->setUpdatedAt(new \DateTime());
        $payer->setFirstName($data['firstName']);
        $payer->setLastName($data['lastName']);
    }
}
