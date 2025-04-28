<?php

namespace App\Controller;

use App\Entity\Benevole;
use App\Form\BenevoleType;
use App\Repository\BenevoleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Form\FormInterface;


#[Route('/admin/benevole')]
class AdminBenevoleController extends AbstractController
{
    
    #[Route('/', name: 'app_benevole')]
    public function index(BenevoleRepository $benevoleRepository, PaginatorInterface $paginator, Request $request): Response
    {
        $query = $benevoleRepository->createQueryBuilder('b')->orderBy('b.id', 'DESC')->getQuery();
    
        $pagination = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1), 
            10 
        );
    
        return $this->render('admin_benevole/index.html.twig', [
            'benevoles' => $pagination,
        ]);
    }
    

    #[Route('/new', name: 'benevole_new')] public function new(Request $request, EntityManagerInterface $em): Response { $benevole = new Benevole();

        $form = $this->createForm(BenevoleType::class, $benevole);
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('cv')->getData();
        
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = transliterator_transliterate(
                    'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                    $originalFilename
                );
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
        
                try {
                    $file->move(
                        $this->getParameter('kernel.project_dir') . '/public/uploads/cv',
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('danger', 'Un problème est survenu lors du téléversement du CV.');
                }
        
                $benevole->setCv($newFilename);
            }
        
            $benevole->setDateCreation(new \DateTimeImmutable());
            $benevole->setDateMiseAJour(new \DateTime());
        
            $em->persist($benevole);
            $em->flush();
        
            return $this->redirectToRoute('app_benevole');
        }
        
        return $this->render('admin_benevole/form.html.twig', [
            'form' => $form->createView(),
            'edit' => false,
            'benevole' => $benevole
        ]);
        
        }
    
        #[Route('/edit/{id}', name: 'benevole_edit')] public function edit(Benevole $benevole, Request $request, EntityManagerInterface $em): Response { $form = $this->createForm(BenevoleType::class, $benevole); $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                $file = $form->get('cv')->getData();
            
                if ($file) {
                    $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                    $safeFilename = transliterator_transliterate(
                        'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                        $originalFilename
                    );
                    $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
            
                    try {
                        $file->move(
                            $this->getParameter('kernel.project_dir') . '/public/uploads/cv',
                            $newFilename
                        );
                        $benevole->setCv($newFilename);
                    } catch (FileException $e) {
                        $this->addFlash('danger', 'Erreur lors du téléversement du nouveau CV.');
                    }
                }
            
                $benevole->setDateMiseAJour(new \DateTime());
                $em->flush();
            
                return $this->redirectToRoute('app_benevole');
            }
            
            return $this->render('admin_benevole/form.html.twig', [
                'form' => $form->createView(),
                'edit' => true,
                'benevole' => $benevole
            ]);
            
            }
    
    private function handleCvUpload(FormInterface $form, Benevole $benevole): void
    {
        $file = $form->get('cv')->getData();
    
        if ($file) {
            $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = transliterator_transliterate(
                'Any-Latin; Latin-ASCII; [^A-Za-z0-9_] remove; Lower()',
                $originalFilename
            );
            $newFilename = $safeFilename . '-' . uniqid() . '.' . $file->guessExtension();
    
            try {
                $file->move(
                    $this->getParameter('kernel.project_dir') . '/public/uploads/cv',
                    $newFilename
                );
            } catch (FileException $e) {
                $this->addFlash('danger', 'Un problème est survenu lors du téléversement du CV.');
                return;
            }
    
            // Supprimer l’ancien fichier s’il existe
            $oldFilename = $benevole->getCv();
            if ($oldFilename) {
                $oldPath = $this->getParameter('kernel.project_dir') . '/public/uploads/cv/' . $oldFilename;
                if (file_exists($oldPath)) {
                    unlink($oldPath);
                }
            }
    
            $benevole->setCv($newFilename);
        }
    }

    #[Route('/delete/{id}', name: 'benevole_delete')]
    public function delete(Benevole $benevole, EntityManagerInterface $em): Response
    {
        $em->remove($benevole);
        $em->flush();

        return $this->redirectToRoute('app_benevole');
    }
}
