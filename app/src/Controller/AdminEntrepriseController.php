<?php

namespace App\Controller;

use App\Entity\Entreprise;
use App\Form\EntrepriseType;
use App\Repository\EntrepriseRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class AdminEntrepriseController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    // Injection de EntityManagerInterface via le constructeur
    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    #[Route('/admin/entreprises', name: 'app_entreprise')]
    public function index(EntrepriseRepository $entrepriseRepository): Response
    {
        // Récupérer toutes les entreprises
        $entreprises = $entrepriseRepository->findAll();

        return $this->render('admin/entreprise/index.html.twig', [
            'entreprises' => $entreprises,
        ]);
    }

    #[Route('/admin/entreprise/new', name: 'entreprise_new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $entreprise = new Entreprise();
        $form = $this->createForm(EntrepriseType::class, $entreprise);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
    
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();
    
            if ($logoFile) {
                // Génère le nouveau nom à partir du nom de l'entreprise
                $newFilename = $entreprise->getNom() . '.' . $logoFile->guessExtension();
    
                try {
                    $logoFile->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/logo_entreprises',
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo.');
                    return $this->render('admin_entreprise/new.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }
    
                $entreprise->setLogo($newFilename);
            }
    
            $this->entityManager->persist($entreprise);
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Entreprise ajoutée avec succès !');
            return $this->redirectToRoute('app_entreprise');
        }
    
        return $this->render('admin_entreprise/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/entreprise/{id}/edit', name: 'entreprise_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Entreprise $entreprise): Response
    {
        $form = $this->createForm(EntrepriseType::class, $entreprise);
    
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
    
            /** @var UploadedFile $logoFile */
            $logoFile = $form->get('logo')->getData();
    
            if ($logoFile) {
                // Chemin vers les logos
                $logoDirectory = $this->getParameter('kernel.project_dir') . '/public/uploads/logo_entreprises';
                // Nom du fichier actuel
                $oldLogo = $entreprise->getLogo();
                // Nouveau nom basé sur le nom de l'entreprise
                $newFilename = $entreprise->getNom() . '.' . $logoFile->guessExtension();
    
                try {
                    // Supprime l'ancien logo s'il existe
                    if ($oldLogo && file_exists($logoDirectory . '/' . $oldLogo)) {
                        unlink($logoDirectory . '/' . $oldLogo);
                    }
    
                    // Upload du nouveau logo
                    $logoFile->move($logoDirectory, $newFilename);
    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload du logo.');
                    return $this->render('admin_entreprise/edit.html.twig', [
                        'form' => $form->createView(),
                        'entreprise' => $entreprise,
                    ]);
                }
    
                // Met à jour le nom du logo dans la BDD
                $entreprise->setLogo($newFilename);
            }
    
            $this->entityManager->flush();
    
            $this->addFlash('success', 'Entreprise mise à jour avec succès !');
            return $this->redirectToRoute('app_entreprise');
        }
    
        return $this->render('admin_entreprise/edit.html.twig', [
            'form' => $form->createView(),
            'entreprise' => $entreprise,
        ]);
    }

    #[Route('/{id}', name: 'entreprise_delete', methods: ['POST'])]
    public function delete(Request $request, Entreprise $entreprise, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $entreprise->getId(), $request->request->get('_token'))) {
            $em->remove($entreprise);
            $em->flush();
        }
    
        return $this->redirectToRoute('app_entreprise');
    }
    
}
