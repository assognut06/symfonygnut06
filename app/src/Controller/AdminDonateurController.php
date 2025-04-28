<?php

namespace App\Controller;

use App\Entity\Donateur;
use App\Entity\PersonnePhysique;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Request;
use App\Repository\DonateurRepository;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Form\SocieteType;
use App\Form\PersonnePhysiqueType;



class AdminDonateurController extends AbstractController
{
    //Recupérer tous les donateurs
    #[Route('/admin/donateurs', name: 'admin_donateurs')]
    public function list(
        Request $request,
        DonateurRepository $donateurRepository,
        PaginatorInterface $paginator
    ): Response {
        // Récupérer le numéro de la page depuis la requête
        $page = $request->query->getInt('page', 1); 
        $limit = 10;
    
        // Pagination des donateurs
        $query = $donateurRepository->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC')
            ->getQuery();
    
        $donateurs = $paginator->paginate(
            $query, 
            $page, 
            $limit
        );
    
        return $this->render('admin_donateur/donateurs.html.twig', [
            'donateurs' => $donateurs,
        ]);
    }


    #[Route('/admin/donateur/{id}/edit', name: 'admin_donateur_details')]
    public function edit(Donateur $donateur, Request $request, EntityManagerInterface $em): Response
    {

        if ($donateur->getTypeDonateur() == "societe") {
            $form = $this->createForm(SocieteType::class, $donateur);
        } else if ($donateur->getTypeDonateur() == "personne_physique") {
            $form = $this->createForm(PersonnePhysiqueType::class, $donateur);
        }
    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Le donateur a été modifié.');
            return $this->redirectToRoute('admin_donateur_show', ['id' => $donateur->getId()]);
        }
    
        return $this->render('admin_donateur/details.html.twig', [
            'form' => $form->createView(),
            'donateur' => $donateur
        ]);
    }
    
    #[Route('/admin/donateur/{id}/delete', name: 'admin_donateur_delete', methods: ['POST'])]
    public function delete(Donateur $donateur, Request $request, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete_donateur_' . $donateur->getId(), $request->request->get('_token'))) {
            $em->remove($donateur);
            $em->flush();
            $this->addFlash('success', 'Le donateur a été supprimé avec succès.');
        } else {
            $this->addFlash('danger', 'Jeton CSRF invalide. La suppression a échoué.');
        }
    
        return $this->redirectToRoute('admin_donateurs');
    }
    

}
