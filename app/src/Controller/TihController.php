<?php

namespace App\Controller;

use App\Entity\Tih;
use App\Form\TihType;
use App\Service\TihApplicationWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class TihController extends AbstractController
{
    #[Route('/espace-tih/cv', name: 'espace_tih_cv')]
    #[IsGranted('ROLE_USER')]
    public function downloadCv(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $tih = $user->getTih();

        if (!$tih || !$tih->getCv()) {
            throw $this->createNotFoundException('CV introuvable.');
        }

        $fileName = basename((string) $tih->getCv());
        $cvPath = rtrim((string) $this->getParameter('cv_tih_directory'), '/') . '/' . $fileName;

        if (!is_file($cvPath)) {
            $legacyPath = rtrim((string) $this->getParameter('kernel.project_dir'), '/') . '/public/uploads/tihcv/' . $fileName;
            if (is_file($legacyPath)) {
                $cvPath = $legacyPath;
            } else {
                throw $this->createNotFoundException('CV introuvable.');
            }
        }

        $response = new BinaryFileResponse($cvPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);

        return $response;
    }

    #[Route('/espace-tih/attestation', name: 'espace_tih_attestation')]
    #[IsGranted('ROLE_USER')]
    public function downloadAttestation(): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $tih = $user->getTih();

        if (!$tih || !$tih->getAttestationTih()) {
            throw $this->createNotFoundException('Attestation introuvable.');
        }

        $fileName = basename((string) $tih->getAttestationTih());
        $attestationPath = rtrim((string) $this->getParameter('attestation_tih_directory'), '/') . '/' . $fileName;

        if (!is_file($attestationPath)) {
            $legacyPath = rtrim((string) $this->getParameter('kernel.project_dir'), '/') . '/public/uploads/tihattest/' . $fileName;
            if (is_file($legacyPath)) {
                $attestationPath = $legacyPath;
            } else {
                throw $this->createNotFoundException('Attestation introuvable.');
            }
        }

        $response = new BinaryFileResponse($attestationPath);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $fileName);

        return $response;
    }

    #[Route('/espace-tih', name: 'espace_tih')]
    #[IsGranted('ROLE_USER')]
    public function profiltih(
        Request $request,
        EntityManagerInterface $em,
        SluggerInterface $slugger,
        TihApplicationWorkflowService $workflow,
    ): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $tih = $user->getTih();
        $initialSubmission = null === $tih;
        $showForm = $request->query->get('edit') === '1' || !$tih;

        if (!$tih) {
            $tih = new Tih();
            $tih->setUser($user);
            $tih->setProfileKind(Tih::PROFILE_KIND_REAL);
            $tih->setCreatedAt(new \DateTime());
            $tih->setUpdatedAt(new \DateTime());
            $tih->setIsValidate(false);
        }

        $formView = null;

        if ($showForm) {
            $form = $this->createForm(TihType::class, $tih);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $tih->setProfileKind(Tih::PROFILE_KIND_REAL);
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

                /** @var UploadedFile|null $photoFile */
                $photoFile = $form->get('photo')->getData();
                if ($photoFile) {
                    $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = $slugger->slug($originalFilename)->lower();
                    $newFilename = uniqid() . '-' . $safeFilename . '.' . $photoFile->guessExtension();

                    $photoFile->move($this->getParameter('kernel.project_dir') . '/public/uploads/tih', $newFilename);
                    $tih->setPhoto($newFilename);
                }

                // Ajout du rôle ROLE_TIH si non présent
                $roles = $user->getRoles();
                if (!in_array('ROLE_TIH', $roles)) {
                    $roles[] = 'ROLE_TIH';
                    $user->setRoles($roles);
                }

                $tih->setUpdatedAt(new \DateTime());
                $workflow->submitForReview($tih, $user, $initialSubmission);

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
            'demoMode' => false,
        ]);
    }

    #[Route('/tih/demo/ajouter', name: 'app_tih_demo_new', methods: ['GET', 'POST'])]
    public function newDemo(Request $request, EntityManagerInterface $em): Response
    {
        $tih = new Tih();
        $tih->setProfileKind(Tih::PROFILE_KIND_DEMO);
        $tih->setApplicationStatus(Tih::STATUS_PENDING);

        $form = $this->createForm(TihType::class, $tih, ['demo_mode' => true]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // La route impose la nature du profil : elle ne dépend jamais d'une valeur du formulaire.
            $tih->setProfileKind(Tih::PROFILE_KIND_DEMO);
            $tih->setApplicationStatus(Tih::STATUS_PENDING);
            $tih->setPhoto(null);
            $tih->setCv(null);
            $tih->setAttestationTih(null);

            $em->persist($tih);
            $em->flush();

            $this->addFlash('success', 'Le profil fictif a été proposé. Il sera visible après validation par un administrateur.');

            return $this->redirectToRoute('app_tih_search');
        }

        return $this->render('tih/profil_tih.html.twig', [
            'tih' => $tih,
            'form' => $form->createView(),
            'demoMode' => true,
        ]);
    }
}
