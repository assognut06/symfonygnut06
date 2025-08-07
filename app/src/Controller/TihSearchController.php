<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Repository\TihRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

#[Route('/tih')]
class TihSearchController extends AbstractController
{
    #[Route('/tih_search', name: 'app_tih_search')]
    public function index(TihRepository $tihRepository): Response
    {
        $tihs = $tihRepository->findAll();

        return $this->render('tih_search/index.html.twig', [
            'tihs' => $tihs,
        ]);
    }

    #[Route('/tih/{id}', name: 'app_tih_details')]
    public function details(TihRepository $tihRepository, int $id): Response
    {
        $tih = $tihRepository->find($id);

        if (!$tih) {
            throw $this->createNotFoundException('TIH non trouvé.');
        }

        return $this->render('tih_search/details.html.twig', [
            'tih' => $tih,
        ]);
    }

    #[Route('/tih/{id}/contact', name: 'app_tih_contact')]
    public function contact(Request $request, MailerInterface $mailer, TihRepository $tihRepository, int $id): Response
    {
        $tih = $tihRepository->find($id);

        if (!$tih) {
            throw $this->createNotFoundException('TIH non trouvé.');
        }

        $form = $this->createFormBuilder()
            ->add('nom', TextType::class, [
                'label' => 'Votre nom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Votre prénom',
                'attr' => ['class' => 'form-control']
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'required' => false,
                'attr' => ['class' => 'form-control']
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control']
            ])
            ->add('email', TextType::class, [
                'label' => 'Adresse email',
                'attr' => ['class' => 'form-control']
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet',
                'attr' => ['class' => 'form-control']
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['class' => 'form-control', 'rows' => 6]
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $htmlContent = $this->renderView('mailjet/contact_tih.html.twig', [
                'prenom' => $data['prenom'],
                'nom' => $data['nom'],
                'entreprise' => $data['entreprise'],
                'telephone' => $data['telephone'],
                'email' => $data['email'],
                'message' => $data['message'],
            ]);

            $email = (new Email())
                ->from('gnut@gnut06.org')
                ->to($tih->getEmailPro())
                //  ->addTo('gnut@gnut06.org')
                ->subject($data['subject'])
                ->html($htmlContent)
                ->embedFromPath(
                    $this->getParameter('kernel.project_dir') . '/public/images/logo_trans-min_300.png',
                    'eye-image'
                );

            $mailer->send($email);

            $this->addFlash('success', 'Votre message a été envoyé à ' . $tih->getPrenom());

            return $this->redirectToRoute('app_tih_details', ['id' => $id]);
        }

        return $this->render('tih_search/contact.html.twig', [
            'form' => $form->createView(),
            'tih' => $tih,
        ]);
    }
}
