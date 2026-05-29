<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfilePictureType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;

#[IsGranted('ROLE_USER')]
class UserController extends AbstractController
{
    #[Route('/profile/edit-picture', name: 'edit_profile_picture', methods: ['GET', 'POST'])]
    public function editProfilePicture(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
    ): Response {
        // Récupérer l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        // Créer le formulaire
        $form = $this->createForm(ProfilePictureType::class);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $photoFile = $form->get('photo')->getData();

            if ($photoFile instanceof UploadedFile) {
                try {
                    $photosDirectory = (string) $this->getParameter('photos_directory');
                    $this->ensureDirectoryIsReady($photosDirectory);

                    // Générer un nouveau nom pour la photo
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = (string) $slugger->slug($originalFilename ?: 'photo');
                    $extension = $photoFile->guessExtension() ?: $photoFile->getClientOriginalExtension() ?: 'bin';
                    $newFilename = $safeFilename . '-' . uniqid('', true) . '.' . $extension;

                    // Déplacer le fichier vers le dossier de destination
                    $photoFile->move(
                        $photosDirectory,
                        $newFilename
                    );

                    $oldPhotoPath = null;
                    if ($user->getProfilePicture()) {
                        $oldPhotoPath = $photosDirectory . '/' . $user->getProfilePicture();
                    }

                    // Mémoriser le nom du fichier en BDD
                    $user->setProfilePicture($newFilename);
                    $entityManager->flush();

                    // Supprime l'ancienne photo uniquement après mise à jour réussie
                    if ($oldPhotoPath && file_exists($oldPhotoPath)) {
                        @unlink($oldPhotoPath);
                    }

                    // Message flash de succès
                    $this->addFlash('success', 'Votre photo de profil a bien été mise à jour.');
                } catch (\Throwable $e) {
                    error_log(sprintf('Erreur upload photo profil (user_id=%s): %s', (string) $user->getId(), $e->getMessage()));

                    // Message flash en cas d'erreur
                    $this->addFlash('danger', 'Une erreur est survenue lors de l\'upload de votre photo. Veuillez réessayer.');
                }
            }

            // Rediriger vers la page de profil
            return $this->redirectToRoute('app_profil');
        }

        if ($form->isSubmitted() && !$form->isValid()) {
            $this->addFlash('danger', 'La photo choisie n\'est pas valide. Vérifiez le format et la taille du fichier.');
        }

        // Afficher le formulaire
        return $this->render('profil/edit_picture.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/profile/delete-picture', name: 'delete_profile_picture', methods: ['POST'])]
    public function deleteProfilePicture(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Récupérer l'utilisateur connecté
        /** @var User $user */
        $user = $this->getUser();

        if (!$this->isCsrfTokenValid('delete_profile_picture' . $user->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'La suppression de la photo a expiré. Veuillez réessayer.');

            return $this->redirectToRoute('app_profil');
        }

        // Vérifier si l'utilisateur a une photo de profil
        if ($user->getProfilePicture()) {
            $photoFilename = $user->getProfilePicture();
            $photoPath = $this->getParameter('photos_directory') . '/' . $photoFilename;

            try {
                // Supprimer la référence en base de données
                $user->setProfilePicture(null);
                $entityManager->flush();

            } catch (\Throwable $e) {
                $this->addFlash('danger', 'Impossible de supprimer la photo pour le moment (droits fichiers serveur).');

                return $this->redirectToRoute('app_profil');
            }

            // Suppression physique best-effort: on n'annule pas la suppression logique si le fichier est verrouille.
            if (file_exists($photoPath) && !@unlink($photoPath)) {
                $this->addFlash('warning', 'Photo retirée du profil, mais fichier non supprimé sur le serveur.');
            } else {
                $this->addFlash('success', 'Votre photo de profil a été supprimée.');
            }
        } else {
            $this->addFlash('warning', 'Aucune photo de profil à supprimer.');
        }

        // Rediriger vers la page de profil
        return $this->redirectToRoute('app_profil');
    }

    private function ensureDirectoryIsReady(string $directory): void
    {
        if (!is_dir($directory) && !mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new FileException(sprintf('Impossible de creer le dossier %s', $directory));
        }

        if (!is_writable($directory)) {
            throw new FileException(sprintf('Le dossier %s n\'est pas accessible en ecriture', $directory));
        }
    }
}
