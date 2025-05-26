<?php

namespace App\Controller;

use App\Entity\Don;
use App\Entity\Casque;
use App\Entity\Donateur;
use App\Form\DonCasqueType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class DonCasqueController extends AbstractController
{
    #[Route('/don-casque/new', name: 'app_don_casque_new')]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer
    ): Response {
        $session = $request->getSession();

        // Récupérer l'ID et l'email du donateur
        $donateurId = $session->get('selected_donateur_id');

        if (!$donateurId) {
            throw $this->createNotFoundException('Aucun donateur sélectionné.');
        }

        $donateur = $entityManager->getRepository(Donateur::class)->find($donateurId);

        if (!$donateur) {
            throw $this->createNotFoundException('Le donateur avec l\'ID ' . $donateurId . ' n\'existe pas.');
        }

        // Récupérer l'email du donateur depuis la base de données
        $donateurEmail = $donateur->getEmail();

        // Créer un nouveau don et l'associer au donateur
        $don = new Don();
        $form = $this->createForm(DonCasqueType::class, $don);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Définir les dates et le statut pour le don

            $now = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
            $don
                ->setDonateur($donateur)
                ->setDateCreation($now)
                ->setDateMiseAJour(new \DateTime('now', new \DateTimeZone('Europe/Paris')));

                if ($don->getModeLivraison()->getNom() === 'Expédition'){
                    $don->setStatut('En attente de bordereau');
                }
                elseif ($don->getModeLivraison()->getNom() === 'Dépôt'){
                    $don->setStatut('En attente de dépôt');
                }

            // Définir les dates pour chaque casque
            foreach ($don->getCasques() as $casque) {
                $casque
                    ->setDateCreation($now)
                    ->setDateMiseAJour(new \DateTime('now', new \DateTimeZone('Europe/Paris')));
            }

            // Persister le don et ses casques
            $entityManager->persist($don);
            $entityManager->flush();

            // Envoi des emails
            try {
                // Email au donateur
                $emailDonateur = (new TemplatedEmail())
                    ->from(new Address('gnut@gnut06.org', 'Gnut 06'))
                    ->to($donateurEmail)
                    ->subject('Merci pour votre don !')
                    ->htmlTemplate('don_casque/email_donateur.html.twig')
                    ->context([
                        'donateur' => $donateur,
                        'don' => $don,
                    ]);

                $mailer->send($emailDonateur);

                $this->addFlash('success', 'Votre don a été enregistré avec succès. Un email de confirmation vous a été envoyé.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi des emails.' .$e);
            }


                try {
                // Email à Gnut 06
                $emailGnut = (new TemplatedEmail())
                    ->from(new Address('gnut@gnut06.org', 'Gnut 06'))
                    ->to('doncasque@gnut06.org') //Mail du sécuritariat
                    ->subject('Nouveau don reçu')
                    ->htmlTemplate('don_casque/email_gnut_notification.html.twig')
                    ->context([
                        'donateur' => $donateur,
                        'don' => $don,
                    ]);

                $mailer->send($emailGnut);

                $this->addFlash('success', 'Un email a été envoyé à Gnut 06 également.');
            } catch (\Exception $e) {
                $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi des emails.' .$e);
            }

            // Redirection vers une autre route sans ID dans l'URL
            return $this->redirectToRoute('app_dons_par_donateurid');
        }

        return $this->render('don_casque/new.html.twig', [
            'form' => $form->createView(),
            'donateur' => $donateur,
        ]);
    }

    #[Route('/don-casque/list-dons', name: 'app_dons_par_donateurid')]
    public function list(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $request->getSession();

        // Récupérer l'ID du donateur depuis la session
        $donateurId = $session->get('selected_donateur_id');

        if (!$donateurId) {
            throw $this->createNotFoundException('Aucun donateur sélectionné.');
        }

        // Récupérer le donateur à partir de l'ID
        $donateur = $entityManager->getRepository(Donateur::class)->find($donateurId);

        if (!$donateur) {
            throw $this->createNotFoundException('Le donateur avec l\'ID ' . $donateurId . ' n\'existe pas.');
        }

        // Récupérer tous les dons du donateur
        $dons = $entityManager->getRepository(Don::class)->findBy(['donateur' => $donateur]);

        return $this->render('don_casque/list.html.twig', [
            'donateur' => $donateur,
            'dons' => $dons,
        ]);
    }
}