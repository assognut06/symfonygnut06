<?php

namespace App\Controller;

use App\Entity\Don;
use App\Entity\Donateur;
use App\Repository\DonRepository;
use App\Repository\CasqueRepository;
use App\Repository\PartenaireLogistiqueRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mime\Address;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;


class AdminDonsController extends AbstractController
{

#[Route('/admin/dons/{id}', name: 'admin_dons', requirements: ['id' => '\d*'], defaults: ['id' => null])]
public function adminDons(
    Request $request,
    DonRepository $donRepository,
    PartenaireLogistiqueRepository $partenaireLogistiqueRepository,
    PaginatorInterface $paginator,
    ?Donateur $donateur = null,
): Response {
    $page = $request->query->getInt('page', 1);
    $limit = 10;

    if ($donateur) {
        $query = $donRepository->TrouveDonsParDonateur($donateur);
    } else {
        $query = $donRepository->createQueryBuilder('d')
            ->orderBy('d.id', 'DESC')
            ->getQuery();
    }

    // Pagination des résultats
    $dons = $paginator->paginate($query, $page, $limit);

    $partenairesLogistiques = $partenaireLogistiqueRepository->findAll();

    return $this->render('admin_don_casque/dons.html.twig', [
        'donateur' => $donateur,
        'dons' => $dons,
        'partenairesLogistiques' => $partenairesLogistiques,
    ]);
}
    

    // Récupérer tous ou les casques associés au don
    #[Route('/admin/don/{id}/casques', name: 'admin_casques', requirements: ['id' => '\d*'], defaults: ['id' => null])]
    public function showCasques(
        Request $request,
        CasqueRepository $casqueRepository,
        PaginatorInterface $paginator,
        ?Don $don = null
    ): Response {
        $page = $request->query->getInt('page', 1);
        $limit = 10;
    
        if ($don) {
            $query = $casqueRepository->TrouveCasquesParDon($don);
        } else {
            $query = $casqueRepository->createQueryBuilder('c')
                ->orderBy('c.id', 'DESC')
                ->getQuery();
        }
    
        $casques = $paginator->paginate($query, $page, $limit);
    
        return $this->render('admin_don_casque/casques.html.twig', [
            'don' => $don,
            'casques' => $casques,
        ]);
    }


    //Mise a jour partenaire logistique
    #[Route('/admin/dons/{id}/update-partenaire', name: 'admin_update_partenaire', methods: ['POST'])]
    public function updatePartenaire(Request $request, Don $don, EntityManagerInterface $entityManager, PartenaireLogistiqueRepository $partenaireLogistiqueRepository): Response
    {
        // Récupérer l'ID du partenaire logistique depuis le corps de la requête JSON
        $data = json_decode($request->getContent(), true);
        $partenaireId = $data['partenaire_id'] ?? null;
    
        if (!$partenaireId) {
            return $this->json(['success' => false, 'message' => 'Aucun partenaire sélectionné.'], 400);
        }
    
        // Rechercher le partenaire logistique correspondant
        $partenaireLogistique = $partenaireLogistiqueRepository->find($partenaireId);
    
        if (!$partenaireLogistique) {
            return $this->json(['success' => false, 'message' => 'Partenaire non trouvé.'], 404);
        }
    
        // Mettre à jour directement le partenaire logistique du don
        $don->setPartenaireLogistique($partenaireLogistique);
        $don->setDateMiseAJour(new \DateTime());

        $entityManager->flush();
    
        // Renvoyer une réponse JSON avec le nom du partenaire mis à jour
        return $this->json([
            'success' => true,
            'nom' => $partenaireLogistique->getNom(),
        ]);
    }


    #[Route('/admin/dons/{id}/update-statut', name: 'admin_update_statut', methods: ['POST'])]
    public function updateStatut(Request $request, Don $don, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('update_statut' . $don->getId(), $request->request->get('_token'))) {
            if ($don->getStatut() === 'Bordereau envoyé') {
                $don->setStatut('Don reçu');
                $em->flush();
                $this->addFlash('success', 'Le don a été marqué comme reçu.');
            }
        }
    
        return $this->redirectToRoute('admin_dons');
    }

  
    #[Route('/admin/dons/{id}/annuler-statut', name: 'admin_annuler_statut', methods: ['POST'])]
    public function annulerStatut(Request $request, Don $don, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('annuler_statut' . $don->getId(), $request->request->get('_token'))) {
            $don->setStatut('Bordereau envoyé');
            $em->flush();
            $this->addFlash('success', 'Statut annulé avec succès.');
        }

        return $this->redirectToRoute('admin_dons');
    }

    
    // Envoi du bordereau
    #[Route('/admin/dons/{id}/envoyer-bordereau', name: 'admin_send_bordereau', methods: ['POST'])]
    public function sendBordereau(
        Request $request,
        Don $don,
        MailerInterface $mailer,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger
    ): Response {
        // Vérifier si un partenaire logistique est associé au don
        if (!$don->getPartenaireLogistique()) {
            $this->addFlash('danger', 'Le partenaire logistique est manquant ou invalide.');
            return $this->redirectToRoute('admin_dons');
        }

        // Récupérer le fichier PDF et le numéro de suivi
        $bordereauFile = $request->files->get('bordereau');
        $numeroSuivi = trim($request->request->get('numero_suivi'));

        if (!$bordereauFile) {
            $this->addFlash('danger', 'Veuillez télécharger un fichier PDF.');
            return $this->redirectToRoute('admin_dons');
        }

        // Enregistrement du fichier dans le dossier uploads/bordereau
        $originalFilename = pathinfo($bordereauFile->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $slugger->slug($originalFilename);
        $newFilename = $safeFilename . '-' . uniqid() . '.' . $bordereauFile->guessExtension();

        try {
            $bordereauFile->move(
                $this->getParameter('bordereau_directory'),
                $newFilename
            );
            // Stocker le nom du fichier ou son chemin relatif dans la BDD
            $don->setBordereau($newFilename);
        } catch (FileException $e) {
            $this->addFlash('danger', 'Erreur lors de l\'enregistrement du fichier : ' . $e->getMessage());
            return $this->redirectToRoute('admin_dons');
        }

        // Mettre à jour le numéro de suivi (si fourni)
        if ($numeroSuivi) {
            $don->setNumeroSuivi($numeroSuivi);
        }

        try {
            // Création de l'email au donateur
            $emailDonateur = (new TemplatedEmail())
                ->from(new Address('gnut@gnut06.org', 'Gnut 06'))
                ->to($don->getDonateur()->getEmail())
                ->subject('Votre bordereau de suivi')
                ->htmlTemplate('admin_don_casque/email_bordereau.html.twig')
                ->context([
                    'donateur' => $don->getDonateur(),
                    'don' => $don,
                    'numero_suivi' => $numeroSuivi,
                    'transporteur' => $don->getPartenaireLogistique(),
                ])
                ->attachFromPath($this->getParameter('bordereau_directory') . '/' . $don->getBordereau());

            // Envoyer l'email
            $mailer->send($emailDonateur);

            $don->setStatut('Bordereau envoyé');
            $don->setDateMiseAJour(new \DateTime());
            $entityManager->flush();

            $this->addFlash('success', 'Le bordereau a été envoyé avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Une erreur est survenue lors de l\'envoi de l\'email : ' . $e->getMessage());
        }

        return $this->redirectToRoute('admin_dons');
    }
}