<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use App\Entity\Payers;
use App\Entity\HelloAssoFormNotification;
use App\Entity\AssoRecommander;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class NotificationController extends AbstractController
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(EntityManagerInterface $em, LoggerInterface $logger)
    {
        $this->em = $em;
        $this->logger = $logger;
    }

    #[Route('/notification/callback', name: 'notification_callback', methods: ['POST'])]
    public function callback(Request $request, MailerInterface $mailer): Response
    {
        try {
            // ✅ VÉRIFICATION DE SÉCURITÉ : IP HelloAsso
            $expectedIp = '51.138.206.200';
            $clientIp = $request->getClientIp();
            /*
            if ($clientIp !== $expectedIp) {
                $this->logger->warning('Unauthorized access attempt from IP', [
                    'ip' => $clientIp,
                    'expected' => $expectedIp,
                    'user_agent' => $request->headers->get('User-Agent')
                ]);
                return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
            }
            */
            // ✅ VALIDATION DU CONTENU JSON
            $content = $request->getContent();
            $data = json_decode($content, true);
    
            if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON received', [
                    'content' => substr($content, 0, 500), // Limiter les logs
                    'json_error' => json_last_error_msg()
                ]);
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            // ✅ LOG DE LA NOTIFICATION REÇUE
            $this->logger->info('HelloAsso notification received', [
                'eventType' => $data['eventType'] ?? 'unknown',
                'organizationSlug' => $data['data']['organizationSlug'] ?? null,
                'formSlug' => $data['data']['formSlug'] ?? null,
                'timestamp' => date('Y-m-d H:i:s')
            ]);

            // ✅ TRAITEMENT PRINCIPAL : Notification HelloAsso
            $this->processHelloAssoNotification($data);
    
            // ✅ TRAITEMENT CONDITIONNEL : Payers (si données présentes)
            if (isset($data['data']['payer'])) {
                $this->processPayerData($data);
            }

            // ✅ TRAITEMENT CONDITIONNEL : Items de commande (si présents)
            if (isset($data['data']['items']) && is_array($data['data']['items'])) {
                $this->processOrderItems($data['data']['items'], $data);
            }
    
            // ✅ ENVOI D'EMAIL DE NOTIFICATION
            $this->sendNotificationEmail($mailer, $data);
    
            return new JsonResponse([
                'status' => 'success', 
                'message' => 'Notification processed successfully',
                'eventType' => $data['eventType'] ?? null
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Callback processing error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'data' => $data ?? null
            ]);
            
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Internal server error',
                'error_code' => 'CALLBACK_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * ✅ MÉTHODE PRINCIPALE : Traitement des notifications HelloAsso
     */
    private function processHelloAssoNotification(array $data): void
    {
        try {
            // Créer et persister l'entité HelloAssoFormNotification
            $notification = HelloAssoFormNotification::fromHelloAssoPayload($data);
            $this->em->persist($notification);

            // Traitement spécifique selon le type d'événement
            $eventType = $data['eventType'] ?? 'unknown';
            
            switch ($eventType) {
                case 'Form':
                case 'FormPublished':
                case 'FormUpdated':
                    $this->processFormEvent($data['data'] ?? [], $notification);
                    break;
                    
                case 'Order':
                    $this->processOrderEvent($data['data'] ?? [], $notification);
                    break;
                    
                case 'Payment':
                    $this->processPaymentEvent($data['data'] ?? [], $notification);
                    break;
                    
                default:
                    $this->logger->info('Unknown event type processed', ['eventType' => $eventType]);
            }

            // Sauvegarder toutes les modifications
            $this->em->flush();
            
            $this->logger->info('HelloAsso notification processed successfully', [
                'id' => $notification->getId(),
                'eventType' => $notification->getEventType(),
                'formSlug' => $notification->getFormSlug(),
                'organizationSlug' => $notification->getOrganizationSlug(),
                'tierCount' => $notification->getTierCount()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing HelloAsso notification', [
                'error' => $e->getMessage(),
                'eventType' => $data['eventType'] ?? 'unknown',
                'data_keys' => array_keys($data)
            ]);
            throw $e;
        }
    }

    /**
     * ✅ TRAITEMENT DES ÉVÉNEMENTS DE FORMULAIRE
     */
    private function processFormEvent(array $formData, HelloAssoFormNotification $notification): void
    {
        if (empty($formData['organizationSlug'])) {
            return;
        }

        try {
            $organizationSlug = $formData['organizationSlug'];
            
            // Chercher ou créer l'association
            $asso = $this->em->getRepository(AssoRecommander::class)
                ->findOneBy(['organizationSlug' => $organizationSlug]);

            if (!$asso) {
                $asso = new AssoRecommander();
                $asso->setOrganizationSlug($organizationSlug);
                $isNew = true;
            } else {
                $isNew = false;
            }

            // Remplir avec les données disponibles
            $this->updateAssoFromFormData($asso, $formData);
            $this->em->persist($asso);
            
            $this->logger->info('Organization processed from form event', [
                'organizationSlug' => $organizationSlug,
                'isNew' => $isNew,
                'formSlug' => $formData['formSlug'] ?? null
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing form event', [
                'error' => $e->getMessage(),
                'organizationSlug' => $organizationSlug ?? 'unknown'
            ]);
        }
    }

    /**
     * ✅ TRAITEMENT DES ÉVÉNEMENTS DE COMMANDE
     */
    private function processOrderEvent(array $orderData, HelloAssoFormNotification $notification): void
    {
        $this->logger->info('Processing order event', [
            'orderId' => $orderData['id'] ?? null,
            'amount' => $orderData['amount'] ?? null,
            'itemCount' => isset($orderData['items']) ? count($orderData['items']) : 0
        ]);

        // Traitement spécifique aux commandes si nécessaire
        // Par exemple, mise à jour de statistiques, etc.
    }

    /**
     * ✅ TRAITEMENT DES ÉVÉNEMENTS DE PAIEMENT
     */
    private function processPaymentEvent(array $paymentData, HelloAssoFormNotification $notification): void
    {
        $this->logger->info('Processing payment event', [
            'paymentId' => $paymentData['id'] ?? null,
            'state' => $paymentData['state'] ?? null,
            'amount' => $paymentData['amount'] ?? null
        ]);

        // Traitement spécifique aux paiements si nécessaire
    }

    /**
     * ✅ TRAITEMENT DES DONNÉES PAYER
     */
    private function processPayerData(array $data): void
    {
        $payerData = $data['data']['payer'];
        $eventType = $data['eventType'];

        if ($eventType === 'Order') {
            $this->verifyPayer($payerData);
        } elseif ($eventType === 'Payment') {
            $this->logger->info('Payment event for payer', [
                'email' => $payerData['email'] ?? 'unknown',
                'paymentState' => $data['data']['state'] ?? null
            ]);
        }
    }

    /**
     * ✅ TRAITEMENT DES ITEMS DE COMMANDE
     */
    private function processOrderItems(array $items, array $data): void
    {
        foreach ($items as $item) {
            $this->logger->info('Processing order item', [
                'name' => $item['name'] ?? 'unknown',
                'amount' => $item['amount'] ?? 0,
                'type' => $item['type'] ?? 'unknown',
                'state' => $item['state'] ?? 'unknown'
            ]);
            
            // Traitement spécifique des items si nécessaire
            // Par exemple, gestion des stocks, etc.
        }
    }

    /**
     * ✅ MISE À JOUR ASSO DEPUIS DONNÉES FORMULAIRE
     */
    private function updateAssoFromFormData(AssoRecommander $asso, array $formData): void
    {
        $apiData = [
            'name' => $formData['organizationName'] ?? null,
            'logo' => $formData['organizationLogo'] ?? null,
            'banner' => $formData['bannerPublicUrl'] ?? null,
            'url' => $formData['url'] ?? null,
            'description' => $formData['description'] ?? null,
            'type' => $formData['activityType'] ?? null,
            'city' => $formData['placeCity'] ?? null,
            'zipCode' => $formData['placeZipCode'] ?? null,
            'fiscalReceiptEligibility' => $formData['fiscalReceiptEligibility'] ?? null,
            'fiscalReceiptIssuanceEnabled' => $formData['fiscalReceiptIssuanceEnabled'] ?? null
        ];

        // Utiliser la méthode fillFromApiData si elle existe
        if (method_exists($asso, 'fillFromApiData')) {
            $asso->fillFromApiData($apiData);
        } else {
            // Fallback : remplissage manuel
            foreach ($apiData as $key => $value) {
                if ($value !== null) {
                    $setter = 'set' . ucfirst($key);
                    if (method_exists($asso, $setter)) {
                        $asso->$setter($value);
                    }
                }
            }
        }
    }

    /**
     * ✅ MÉTHODE EXISTANTE AMÉLIORÉE : Verification des payers
     */
    public function verifyPayer(array $data): void
    {
        $payerEmail = $data['email'] ?? null;
        
        if (!$payerEmail) {
            $this->logger->warning('Payer data received without email');
            return;
        }

        try {
            // Rechercher le payer existant
            $payer = $this->em->getRepository(Payers::class)->findOneBy(['email' => $payerEmail]);

            if ($payer) {
                // Mise à jour du payer existant
                $this->updatePayer($payer, $data);
                $this->logger->info('Payer updated', [
                    'email' => $payerEmail,
                    'id' => $payer->getId()
                ]);
            } else {
                // Création d'un nouveau payer
                $payer = new Payers();
                $payer->setEmail($payerEmail);
                $payer->setCreatedAt(new \DateTimeImmutable());
                $this->updatePayer($payer, $data);
                $this->em->persist($payer);
                
                $this->logger->info('New payer created', ['email' => $payerEmail]);
            }

            // Pas de flush ici, sera fait dans processHelloAssoNotification
        } catch (\Exception $e) {
            $this->logger->error('Error in verifyPayer', [
                'email' => $payerEmail,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * ✅ MÉTHODE EXISTANTE : Mise à jour des données payer
     */
    private function updatePayer(Payers $payer, array $data): void
    {
        // Mise à jour sécurisée des champs
        if (isset($data['firstName'])) $payer->setFirstName($data['firstName']);
        if (isset($data['lastName'])) $payer->setLastName($data['lastName']);
        if (isset($data['address'])) $payer->setAddress($data['address']);
        if (isset($data['city'])) $payer->setCity($data['city']);
        if (isset($data['zipCode'])) $payer->setZipCode($data['zipCode']);
        if (isset($data['country'])) $payer->setCountry($data['country']);
        if (isset($data['company'])) $payer->setCompany($data['company']);
        
        $payer->setUpdatedAt(new \DateTime());
    }

    /**
     * ✅ ENVOI D'EMAIL DE NOTIFICATION
     */
    private function sendNotificationEmail(MailerInterface $mailer, array $data): void
    {
        try {
            $eventType = $data['eventType'] ?? 'Unknown';
            $organizationName = $data['data']['organizationName'] ?? 'Organisation inconnue';
            $formSlug = $data['data']['formSlug'] ?? 'N/A';
            
            // Email HTML formaté
            $htmlContent = $this->generateEmailHtml($data);
            
            $email = (new Email())
                ->from('gnut@gnut06.org')
                ->to('gnut@gnut06.org')
                ->subject("HelloAsso - {$eventType} - {$organizationName}")
                ->html($htmlContent)
                ->text($this->generateEmailText($data));

            $mailer->send($email);
            
            $this->logger->info('Notification email sent successfully', [
                'eventType' => $eventType,
                'organizationName' => $organizationName
            ]);
            
        } catch (\Exception $e) {
            $this->logger->error('Failed to send notification email', [
                'error' => $e->getMessage()
            ]);
            // Ne pas faire échouer le traitement si l'email échoue
        }
    }

    /**
     * ✅ GÉNÉRATION DU CONTENU HTML DE L'EMAIL
     */
    private function generateEmailHtml(array $data): string
    {
        $eventType = $data['eventType'] ?? 'Unknown';
        $timestamp = date('d/m/Y H:i:s');
        $formData = $data['data'] ?? [];
        
        $html = "<h2>Notification HelloAsso - {$eventType}</h2>";
        $html .= "<p><strong>Reçue le :</strong> {$timestamp}</p>";
        
        if (isset($formData['organizationName'])) {
            $html .= "<p><strong>Organisation :</strong> {$formData['organizationName']}</p>";
        }
        
        if (isset($formData['formSlug'])) {
            $html .= "<p><strong>Formulaire :</strong> {$formData['formSlug']}</p>";
        }
        
        if (isset($formData['title'])) {
            $html .= "<p><strong>Titre :</strong> {$formData['title']}</p>";
        }

        if (isset($formData['payer'])) {
            $payer = $formData['payer'];
            $html .= "<h3>Informations du payeur :</h3>";
            $html .= "<p><strong>Nom :</strong> {$payer['firstName']} {$payer['lastName']}</p>";
            $html .= "<p><strong>Email :</strong> {$payer['email']}</p>";
        }

        $html .= "<hr><h3>Données complètes :</h3>";
        $html .= "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
        
        return $html;
    }

    /**
     * ✅ GÉNÉRATION DU CONTENU TEXTE DE L'EMAIL
     */
    private function generateEmailText(array $data): string
    {
        $eventType = $data['eventType'] ?? 'Unknown';
        $timestamp = date('d/m/Y H:i:s');
        
        $text = "Notification HelloAsso - {$eventType}\n";
        $text .= "Reçue le : {$timestamp}\n\n";
        $text .= "Données complètes :\n";
        $text .= print_r($data, true);
        
        return $text;
    }

    /**
     * ✅ ENDPOINT DE TEST (pour développement)
     */
    #[Route('/notification/test', name: 'notification_test', methods: ['GET'])]
    public function test(): JsonResponse
    {
        return new JsonResponse([
            'status' => 'ok',
            'message' => 'NotificationController is working',
            'timestamp' => date('Y-m-d H:i:s'),
            'entities' => [
                'HelloAssoFormNotification' => class_exists(HelloAssoFormNotification::class),
                'AssoRecommander' => class_exists(AssoRecommander::class),
                'Payers' => class_exists(Payers::class)
            ]
        ]);
    }

    /**
     * ✅ ENDPOINT DE STATS (pour monitoring)
     */
    #[Route('/notification/stats', name: 'notification_stats', methods: ['GET'])]
    public function stats(): JsonResponse
    {
        try {
            $notificationCount = $this->em->getRepository(HelloAssoFormNotification::class)->count([]);
            $payerCount = $this->em->getRepository(Payers::class)->count([]);
            $assoCount = $this->em->getRepository(AssoRecommander::class)->count([]);

            return new JsonResponse([
                'status' => 'success',
                'stats' => [
                    'notifications' => $notificationCount,
                    'payers' => $payerCount,
                    'associations' => $assoCount,
                    'last_updated' => date('Y-m-d H:i:s')
                ]
            ]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => $e->getMessage()
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
        /**
     * 🐛 DEBUG: Voir toutes les données créées
     */
    #[Route('/notification/debug-all', name: 'notification_debug_all', methods: ['GET'])]
    public function debugAll(): JsonResponse
    {
        $notification = $this->em
            ->getRepository(HelloAssoFormNotification::class)
            ->findOneBy(['organizationSlug' => 'gnut-test-association']);
        
        if (!$notification) {
            return new JsonResponse(['error' => 'Notification not found'], 404);
        }
        
        $result = [
            'notification' => [
                'id' => $notification->getId()->toString(),
                'title' => $notification->getTitle(),
                'organization' => $notification->getOrganizationName(),
                'form_slug' => $notification->getFormSlug(),
                'event_type' => $notification->getEventType()
            ],
            'tiers' => [],
            'custom_fields' => []
        ];
        
        // Récupérer les tiers
        foreach ($notification->getTiers() as $tier) {
            $tierData = [
                'id' => $tier->getId()->toString(),
                'external_id' => $tier->getExternalId(),
                'label' => $tier->getLabel(),
                'price' => $tier->getPrice(),
                'tier_type' => $tier->getTierType(),
                'is_favorite' => $tier->getIsFavorite(),
                'custom_fields' => []
            ];
            
            // Récupérer les champs personnalisés du tier
            foreach ($tier->getCustomFields() as $field) {
                $fieldData = [
                    'id' => $field->getId()->toString(),
                    'external_id' => $field->getExternalId(),
                    'label' => $field->getLabel(),
                    'type' => $field->getType(),
                    'required' => $field->getIsRequired(),
                    'values' => $field->getValues(),
                    'has_options' => $field->hasOptions()
                ];
                
                $tierData['custom_fields'][] = $fieldData;
                $result['custom_fields'][] = $fieldData;
            }
            
            $result['tiers'][] = $tierData;
        }
        
        // ❌ ERREUR : JSON_PRETTY_PRINT au mauvais endroit
        // return new JsonResponse($result, 200, [], JSON_PRETTY_PRINT);
        
        // ✅ CORRECTION : Retour normal
        return new JsonResponse($result);
    }
}