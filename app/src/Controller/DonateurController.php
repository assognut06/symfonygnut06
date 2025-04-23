<?php

namespace App\Controller;

use App\Entity\Societe;
use App\Entity\PersonnePhysique;
use App\Form\SocieteType;
use App\Form\PersonnePhysiqueType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use GuzzleHttp\Client;
use Symfony\Component\Form\FormError;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;

class DonateurController extends AbstractController
{
    public function __construct(private EntityManagerInterface $entityManager) {}

    #[Route('/donateur/formulaire', name: 'donateur_formulaire')]
    public function afficherFormulaires(Request $request): Response
    {
        $session = $request->getSession();

        $donateurPhysique = new PersonnePhysique();
        $donateurSociete = new Societe();

        $formPhysique = $this->createForm(PersonnePhysiqueType::class, $donateurPhysique);
        $formSociete = $this->createForm(SocieteType::class, $donateurSociete);

        $formPhysique->handleRequest($request);
        $formSociete->handleRequest($request);

        // Personne physique
        if ($formPhysique->isSubmitted() && $formPhysique->isValid()) {

            if (!$this->verifierRecaptcha($request)) {
                return $this->renderWithForms($formPhysique, $formSociete);
            }

            $donateurPhysique->setDateCreation(new \DateTimeImmutable());
            $donateurPhysique->setDateMiseAJour(new \DateTime());

            try {
                $this->entityManager->persist($donateurPhysique);
                $this->entityManager->flush();

                $session->set('selected_donateur_id', $donateurPhysique->getId());
                $session->set('donateur_email', $donateurPhysique->getEmail());

                $this->addFlash('success', 'Personne physique créée avec succès !');
                return $this->redirectToRoute('app_don_casque_new');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('danger', 'Cet e-mail est déjà utilisé.');
            }
        }

        // Société
        if ($formSociete->isSubmitted() && $formSociete->isValid()) {

            if (!$this->verifierRecaptcha($request)) {
                return $this->renderWithForms($formPhysique, $formSociete);
            }

            $donateurSociete->setDateCreation(new \DateTimeImmutable());
            $donateurSociete->setDateMiseAJour(new \DateTime());

            try {
                $this->entityManager->persist($donateurSociete);
                $this->entityManager->flush();

                $session->set('selected_donateur_id', $donateurSociete->getId());

                $this->addFlash('success', 'Société créée avec succès !');
                return $this->redirectToRoute('app_don_casque_new');
            } catch (UniqueConstraintViolationException $e) {
                $this->addFlash('danger', 'Cet e-mail est déjà utilisé.');
            }
        }

        return $this->renderWithForms($formPhysique, $formSociete);
    }

    private function renderWithForms($formPhysique, $formSociete): Response
    {
        return $this->render('donateur/form_donateur.html.twig', [
            'form_physique' => $formPhysique->createView(),
            'form_societe' => $formSociete->createView(),
            'site_key' => $_ENV['NOCAPTCHA_SITEKEY']
        ]);
    }

    private function verifierRecaptcha(Request $request): bool
    {
        $recaptchaResponse = $request->request->get('g-recaptcha-response');

        if (!$recaptchaResponse) {
            $this->addFlash('danger', 'La vérification reCAPTCHA est requise.');
            return false;
        }

        $client = new Client();
        $response = $client->request('POST', 'https://www.google.com/recaptcha/api/siteverify', [
            'form_params' => [
                'secret' => $_ENV['NOCAPTCHA_SECRET'],
                'response' => $recaptchaResponse,
                'remoteip' => $request->getClientIp()
            ]
        ]);

        $responseData = json_decode($response->getBody());

        if ($_ENV['APP_ENV'] === 'dev') {
            $responseData->score = 0.9;
            $responseData->success = true;
        }

        if (!$responseData->success || $responseData->score < 0.5) {
            $this->addFlash('danger', 'La vérification reCAPTCHA a échoué. Veuillez réessayer.');
            return false;
        }

        return true;
    }
}
