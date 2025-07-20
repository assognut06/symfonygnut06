<?php
 
namespace App\Controller;
 
use KnpU\OAuth2ClientBundle\Client\ClientRegistry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use App\Security\EmailVerifier;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use App\Entity\User;
use App\Service\EmailService;
use Symfony\Bundle\SecurityBundle\Security;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

 
class GoogleController extends AbstractController
{
      private EmailVerifier $emailVerifier;
      private User $user; 
      private LoggerInterface $logger;

    public function __construct(EmailVerifier $emailVerifier, LoggerInterface $logger)
    {
        $this->emailVerifier = $emailVerifier; 
        $this->logger = $logger;    
    }

    #[Route('/connect/google', name: 'connect_google_start')]
    public function connectAction(ClientRegistry $clientRegistry): RedirectResponse
    {
        return $clientRegistry
            ->getClient('google')
            ->redirect([
                'profile', 'email'
            ], []);
    }
 
    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request, Security $security, EmailService $emailService)
    {
        $user = $this->getUser();
        if ($user) {
            // Si l'utilisateur n'est pas encore vérifié, on envoie l'email de confirmation
             try {
                $emailService->sendConfirmationEmail($user);
                $this->addFlash('success', 'Un email de confirmation a été envoyé. Veuillez consulter votre boîte mail.');
            } catch (\Exception $e) {
                $this->logger->error('Erreur envoi email de confirmation', ['exception' => $e]);
                $this->addFlash('danger', 'Problème lors de l\'envoi du mail. Veuillez réessayer.');
            }
            return $this->redirectToRoute('app_profil');
            return $security->login($user, 'form_login', 'main');
        }
        else{
            $this->addFlash('danger', "Erreur lors de l'authentification Google. Veuillez réessayer ou contacter l'administrateur.");
            return $this->redirectToRoute('app_login');
        }
        

        return $this->redirectToRoute('app_profil');
    }
}