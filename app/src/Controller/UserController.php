<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfilePictureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;

class UserController extends AbstractController
{
    #[Route('/profile/edit-picture', name: 'edit_profile_picture')]
    public function editProfilePicture(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // Récupérer l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        // Créer le formulaire
        $form = $this->createForm(ProfilePictureType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile) {
                // Supprimer l'ancienne photo si elle existe
                if ($user->getProfilePicture()) {
                    $oldPhotoPath = $this->getParameter('photos_directory') . '/' . $user->getProfilePicture();
                    if (file_exists($oldPhotoPath)) {
                        unlink($oldPhotoPath);
                    }
                }

                // Générer un nouveau nom pour la photo
                $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $photoFile->guessExtension();

                try {
                    // Déplacer le fichier vers le dossier de destination
                    $photoFile->move(
                        $this->getParameter('photos_directory'),
                        $newFilename
                    );

                    // Mémoriser le nom du fichier en BDD
                    $user->setProfilePicture($newFilename);
                    $entityManager->flush();

                    // Message flash de succès
                    $this->addFlash('success', 'Votre photo de profil a bien été mise à jour.');
                } catch (FileException $e) {
                    // Message flash en cas d'erreur
                    $this->addFlash('error', 'Une erreur est survenue lors de l\'upload de votre photo. Veuillez réessayer.');
                }
            }

            // Rediriger vers la page de profil
            return $this->redirectToRoute('app_profil');
        }

        // Afficher le formulaire
        return $this->render('profil/edit_picture.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/delete-picture', name: 'delete_profile_picture')]
    public function deleteProfilePicture(EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        // Vérifier si l'utilisateur a une photo de profil
        if ($user->getProfilePicture()) {
            // Supprimer le fichier physique
            $photoPath = $this->getParameter('photos_directory') . '/' . $user->getProfilePicture();
            if (file_exists($photoPath)) {
                unlink($photoPath);
            }

            // Supprimer la référence en base de données
            $user->setProfilePicture(null);
            $entityManager->flush();

            // Ajouter un message flash
            $this->addFlash('success', 'Votre photo de profil a été supprimée.');
        } else {
            $this->addFlash('warning', 'Aucune photo de profil à supprimer.');
        }

        // Rediriger vers la page de profil
        return $this->redirectToRoute('app_profil');
    }
}