<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Form\TihType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;

#[Route('/espace-tih')]
#[IsGranted('ROLE_USER')]
class TihController extends AbstractController
{
    #[Route('', name: 'espace_tih')]
    public function profiltih(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $tih = $user->getTih();
        $showForm = $request->query->get('edit') === '1' || !$tih;

        if (!$tih) {
            $tih = new Tih();
            $tih->setUser($user);
            $tih->setDateCreation(new \DateTime());
            $tih->setDateMiseAJour(new \DateTime());
            $tih->setIsValidate(false);
        }

        // Affiche le message de refus s'il existe puis l'efface
        if ($tih->getValidationMessage()) {
            $this->addFlash('danger', $tih->getValidationMessage());
            $tih->setValidationMessage(null);
            $em->persist($tih);
            $em->flush();
        }

        $formView = null;

        if ($showForm) {
            $form = $this->createForm(TihType::class, $tih);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                /** @var UploadedFile|null $cvFile */
                $cvFile = $form->get('cv')->getData();
                if ($cvFile) {
                    $originalFilename = pathinfo($cvFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename)->lower();
                    $newFilename = uniqid() . '-' . $safeFilename . '.' . $cvFile->guessExtension();

                    $cvFile->move($this->getParameter('cv_tih_directory'), $newFilename);
                    $tih->setCv($newFilename);
                }

                /** @var UploadedFile|null $attestationFile */
                $attestationFile = $form->get('attestationTih')->getData();
                if ($attestationFile) {
                    $originalFilename = pathinfo($attestationFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename)->lower();
                    $newFilename = uniqid() . '-' . $safeFilename . '.' . $attestationFile->guessExtension();

                    $attestationFile->move($this->getParameter('attestation_tih_directory'), $newFilename);
                    $tih->setAttestationTih($newFilename);
                }

                // Ajout du rôle ROLE_TIH si non présent
                $roles = $user->getRoles();
                if (!in_array('ROLE_TIH', $roles)) {
                    $roles[] = 'ROLE_TIH';
                    $user->setRoles($roles);
                }

                $tih->setDateMiseAJour(new \DateTime());
                $tih->setIsValidate(false); // retour en attente après modif

                $em->persist($tih);
                $em->flush();

                $this->addFlash('success', 'Votre profil est en attente de validation par un admin.');
                return $this->redirectToRoute('espace_tih');
            }

            $formView = $form->createView();
        }

        return $this->render('tih/profil_tih.html.twig', [
            'tih' => $tih,
            'form' => $formView,
        ]);
    }
}
