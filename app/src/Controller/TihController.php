<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Form\TihType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Security;
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
    public function dashboard(Request $request, EntityManagerInterface $em, SluggerInterface $slugger): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        if (!$user->isTih() || !$user->isVerified()) {
            $this->addFlash('danger', 'Accès refusé : vous devez être un TIH vérifié.');
            return $this->redirectToRoute('app_profil');
        }

        $tih = $user->getTih();

        // Si aucun TIH en base → on prépare la création
        if (!$tih) {
            $tih = new Tih();
            $tih->setUser($user);
            $tih->setDateCreation(new \DateTime());
            $tih->setDateMiseAJour(new \DateTime());

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

                $em->persist($tih);
                $em->flush();

                $this->addFlash('success', 'Profil TIH créé avec succès.');
                // ⚠️ On ne redirige PAS, on affiche les données directement
            } else {
                return $this->render('tih/profil_tih.html.twig', [
                    'tih' => null, // pas encore créé
                    'form' => $form->createView(),
                ]);
            }
        }

        // SI TIH existe (créé juste avant ou déjà existant)
        return $this->render('tih/profil_tih.html.twig', [
            'tih' => $tih,
            'form' => null,
        ]);
    }
}
