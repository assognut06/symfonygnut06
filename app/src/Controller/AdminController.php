<?php
// src/Controller/AdminController.php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function dashboard()
    {
        // Votre logique d'administration ici

        return $this->render('admin/dashbord/dashboard.html.twig');
    }

    // Ajoutez d'autres méthodes au besoin
}