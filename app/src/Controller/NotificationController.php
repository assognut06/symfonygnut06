<?php
namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\IpUtils;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Payers;
use App\Entity\HelloAssoFormNotification;
use App\Entity\AssoRecommander;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Log\LoggerInterface;

class NotificationController extends AbstractController
{
    private EntityManagerInterface $em;
    private LoggerInterface $logger;

    public function __construct(
        EntityManagerInterface $em,
        LoggerInterface $logger,
        private CacheItemPoolInterface $cache,
        private string $webhookAllowedIps,
        private string $webhookSignatureSecret,
        private string $webhookSignatureHeader,
        private string $webhookTimestampHeader,
        private int $webhookReplayTtlSeconds,
        private int $webhookTimestampToleranceSeconds,
        private int $webhookRateLimitMaxRequests,
        private int $webhookRateLimitWindowSeconds,
        private int $webhookMaxPayloadBytes,
    ) {
        $this->em = $em;
        $this->logger = $logger;
    }

    #[Route('/notification/callback', name: 'notification_callback', methods: ['POST'])]
    public function callback(Request $request, MailerInterface $mailer): Response
    {
        $content = $request->getContent();
        $idempotencyKey = null;

        try {
            $securityResponse = $this->validateWebhookRequest($request, $content);

            if ($securityResponse !== null) {
                return $securityResponse;
            }

            // ✅ VALIDATION DU CONTENU JSON
            $data = json_decode($content, true);
    
            if ($data === null || json_last_error() !== JSON_ERROR_NONE) {
                $this->logger->error('Invalid JSON received', [
                    'content' => substr($content, 0, 500), // Limiter les logs
                    'json_error' => json_last_error_msg()
                ]);
                return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
            }

            $idempotencyKey = $this->buildIdempotencyKey($request, $data, $content);

            if (!$this->reserveIdempotencyKey($idempotencyKey)) {
                $this->logger->warning('Duplicate HelloAsso notification ignored', [
                    'idempotency_key' => $idempotencyKey,
                    'eventType' => $data['eventType'] ?? 'unknown',
                    'client_ip' => $request->getClientIp(),
                ]);

                return new JsonResponse([
                    'status' => 'duplicate',
                    'message' => 'Notification already processed',
                    'eventType' => $data['eventType'] ?? null,
                ]);
            }

            // ✅ LOG DE LA NOTIFICATION REÇUE
            $this->logger->info('HelloAsso notification received', [
                'eventType' => $data['eventType'] ?? 'unknown',
                'organizationSlug' => $data['data']['organizationSlug'] ?? null,
                'formSlug' => $data['data']['formSlug'] ?? null,
                'idempotency_key' => $idempotencyKey,
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

            $this->em->flush();
    
            // ✅ ENVOI D'EMAIL DE NOTIFICATION
            $this->sendNotificationEmail($mailer, $data);
    
            return new JsonResponse([
                'status' => 'success', 
                'message' => 'Notification processed successfully',
                'eventType' => $data['eventType'] ?? null
            ]);

        } catch (\Exception $e) {
            if ($idempotencyKey !== null) {
                $this->cache->deleteItem($this->getReplayCacheKey($idempotencyKey));
            }

            $this->logger->error('Callback processing error', [
                'error' => $e->getMessage(),
                'eventType' => $data['eventType'] ?? null,
                'idempotency_key' => $idempotencyKey,
            ]);
            
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Internal server error',
                'error_code' => 'CALLBACK_ERROR'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    private function validateWebhookRequest(Request $request, string $content): ?JsonResponse
    {
        if (strlen($content) > $this->webhookMaxPayloadBytes) {
            $this->logger->warning('HelloAsso notification rejected: payload too large', [
                'client_ip' => $request->getClientIp(),
                'payload_bytes' => strlen($content),
                'max_payload_bytes' => $this->webhookMaxPayloadBytes,
            ]);

            return new JsonResponse(['error' => 'Payload too large'], Response::HTTP_REQUEST_ENTITY_TOO_LARGE);
        }

        if (!$this->isClientIpAllowed($request)) {
            $this->logger->warning('Unauthorized HelloAsso notification IP', [
                'client_ip' => $request->getClientIp(),
                'allowed_ips' => $this->getAllowedIps(),
                'user_agent' => $request->headers->get('User-Agent'),
            ]);

            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->consumeRateLimit($request)) {
            $this->logger->warning('HelloAsso notification rate limit exceeded', [
                'client_ip' => $request->getClientIp(),
                'limit' => $this->webhookRateLimitMaxRequests,
                'window_seconds' => $this->webhookRateLimitWindowSeconds,
            ]);

            return new JsonResponse(['error' => 'Too many requests'], Response::HTTP_TOO_MANY_REQUESTS);
        }

        if (!$this->isTimestampValid($request)) {
            $this->logger->warning('HelloAsso notification rejected: invalid timestamp', [
                'client_ip' => $request->getClientIp(),
                'timestamp_header' => $this->webhookTimestampHeader,
            ]);

            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        if (!$this->isSignatureValid($request, $content)) {
            $this->logger->warning('HelloAsso notification rejected: invalid signature', [
                'client_ip' => $request->getClientIp(),
                'signature_header' => $this->webhookSignatureHeader,
            ]);

            return new JsonResponse(['error' => 'Unauthorized'], Response::HTTP_UNAUTHORIZED);
        }

        return null;
    }

    private function isClientIpAllowed(Request $request): bool
    {
        $allowedIps = $this->getAllowedIps();

        if ($allowedIps === []) {
            return false;
        }

        $clientIp = $request->getClientIp();

        return $clientIp !== null && IpUtils::checkIp($clientIp, $allowedIps);
    }

    /**
     * @return string[]
     */
    private function getAllowedIps(): array
    {
        return array_values(array_filter(array_map('trim', explode(',', $this->webhookAllowedIps))));
    }

    private function consumeRateLimit(Request $request): bool
    {
        $clientIp = $request->getClientIp() ?? 'unknown';
        $window = max(1, $this->webhookRateLimitWindowSeconds);
        $bucket = (int) floor(time() / $window);
        $cacheKey = sprintf('helloasso_webhook_rate_%s_%d', sha1($clientIp), $bucket);
        $item = $this->cache->getItem($cacheKey);
        $count = $item->isHit() ? (int) $item->get() : 0;

        if ($count >= $this->webhookRateLimitMaxRequests) {
            return false;
        }

        $item->set($count + 1);
        $item->expiresAfter($window * 2);
        $this->cache->save($item);

        return true;
    }

    private function isTimestampValid(Request $request): bool
    {
        $timestamp = $request->headers->get($this->webhookTimestampHeader);

        if ($timestamp === null || trim($timestamp) === '') {
            return true;
        }

        if (!ctype_digit($timestamp)) {
            return false;
        }

        return abs(time() - (int) $timestamp) <= $this->webhookTimestampToleranceSeconds;
    }

    private function isSignatureValid(Request $request, string $content): bool
    {
        if (trim($this->webhookSignatureSecret) === '') {
            return true;
        }

        $signatureHeader = (string) $request->headers->get($this->webhookSignatureHeader, '');

        if ($signatureHeader === '') {
            return false;
        }

        $providedSignature = $this->normalizeSignature($signatureHeader);
        $timestamp = $request->headers->get($this->webhookTimestampHeader);
        $payloadsToSign = array_filter([
            $content,
            $timestamp ? $timestamp . '.' . $content : null,
        ]);

        foreach ($payloadsToSign as $payload) {
            $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSignatureSecret);

            if (hash_equals($expectedSignature, $providedSignature)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeSignature(string $signatureHeader): string
    {
        $signatureHeader = trim($signatureHeader);

        if (str_contains($signatureHeader, '=')) {
            $parts = explode('=', $signatureHeader);
            $signatureHeader = trim((string) end($parts));
        }

        return strtolower($signatureHeader);
    }

    /**
     *  @param array<string,mixed> $data
     */

    private function buildIdempotencyKey(Request $request, array $data, string $content): string
    {
        $externalId = $request->headers->get('X-HelloAsso-Event-Id')
            ?? $request->headers->get('X-Webhook-Id')
            ?? $request->headers->get('X-Request-Id')
            ?? $this->getNestedString($data, ['eventId'])
            ?? $this->getNestedString($data, ['id'])
            ?? $this->getNestedString($data, ['data', 'id'])
            ?? $this->getNestedString($data, ['data', 'order', 'id'])
            ?? $this->getNestedString($data, ['data', 'payment', 'id']);

        if ($externalId !== null) {
            return hash('sha256', 'helloasso-event:' . $externalId);
        }

        return hash('sha256', 'helloasso-payload:' . $content);
    }

    /**
     * @param array<string,mixed> $data
     * @param string[] $path
     */
    private function getNestedString(array $data, array $path): ?string
    {
        $value = $data;

        foreach ($path as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function reserveIdempotencyKey(string $idempotencyKey): bool
    {
        $cacheKey = $this->getReplayCacheKey($idempotencyKey);
        $item = $this->cache->getItem($cacheKey);

        if ($item->isHit()) {
            return false;
        }

        $item->set(true);
        $item->expiresAfter($this->webhookReplayTtlSeconds);
        $this->cache->save($item);

        return true;
    }

    private function getReplayCacheKey(string $idempotencyKey): string
    {
        return 'helloasso_webhook_seen_' . $idempotencyKey;
    }

    /**
     * ✅ MÉTHODE PRINCIPALE : Traitement des notifications HelloAsso
     * 
     * @param array{eventType:?string,data:?array{formSlug:?string,formType:?string,title:?string,bannerPublicUrl:?string,description:?string,url:?string,state:?string,currency:?string,organizationSlug:?string,organizationName:?string,organizationLogo:?string,activityType:?string,activityTypeId:?int,startDate:?string,endDate:?string,banner:?array{fileName:?string,publicUrl:?string},logo:?array{fileName:?string,publicUrl:?string},place:?array{address:?string,name:?string,city:?string,zipCode:?string,country:?string},widget:?array{buttonUrl:?string,fullUrl:?string,vignetteHorizontalUrl:?string,vignetteVerticalUrl:?string},tiers:?array{array{id:?int,label:?string,description:?string,tierType:?string,price:?string,vatRate:?string,paymentFrequency:?string,isEligibleTaxReceipt:?bool,isFavorite:?bool,customFields:?array<mixed>}},name:?string,items:?array<mixed>,amount:?int,id:?string,fiscalReceiptEligibility:?bool,fiscalReceiptIssuanceEnabled:?bool,placeCity:?string,placeZipCode:?string}} $data
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
     * @param array{formSlug:?string,organizationSlug:?string,bannerPublicUrl:?string,fiscalReceiptEligibility:?bool,fiscalReceiptIssuanceEnabled:?bool,activityType:?string,organizationLogo:?string,organizationName:?string,placeCity:?string,placeZipCode:?string,description:?string,url:?string} $formData
     */
    private function processFormEvent(array $formData, HelloAssoFormNotification $notification): void
    {

        $organizationSlug = $formData['organizationSlug'];
        if (empty($organizationSlug)) {
            return;
        }

        try {
            
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
                'formSlug' => $formData['formSlug']
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error processing form event', [
                'error' => $e->getMessage(),
                'organizationSlug' => $organizationSlug
            ]);
        }
    }

    /**
     * ✅ TRAITEMENT DES ÉVÉNEMENTS DE COMMANDE
     * @param array{items:?array<mixed>,amount:?int,id:?string,state:?string} $orderData
     */
    private function processOrderEvent(array $orderData, HelloAssoFormNotification $notification): void
    {
        $this->logger->info('Processing order event', [
            'orderId' => $orderData['id'],
            'amount' => $orderData['amount'],
            'itemCount' => isset($orderData['items']) ? count($orderData['items']) : 0
        ]);

        // Traitement spécifique aux commandes si nécessaire
        // Par exemple, mise à jour de statistiques, etc.
    }

    /**
     * ✅ TRAITEMENT DES ÉVÉNEMENTS DE PAIEMENT
     * 
     * @param array{name:?string,amount:?int,id:?string,state:?string} $paymentData
     */
    private function processPaymentEvent(array $paymentData, HelloAssoFormNotification $notification): void
    {
        $this->logger->info('Processing payment event', [
            'paymentId' => $paymentData['id'],
            'state' => $paymentData['state'],
            'amount' => $paymentData['amount']
        ]);

        // Traitement spécifique aux paiements si nécessaire
    }

    /**
     * ✅ TRAITEMENT DES DONNÉES PAYER
     * @param array{eventType:string,data:array{payer:array{firstName:?string,lastName:?string,address:?string,city:?string,zipCode:?string,country:?string,company:?string,email:?string},state:?string}} $data
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
                'paymentState' => $data['data']['state']
            ]);
        }
    }

    /**
     * ✅ TRAITEMENT DES ITEMS DE COMMANDE
     * @param array{amount:?int,type:?string,state:?string} $items
     * @param array<mixed> $data
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
     * @param array{organizationSlug:?string,bannerPublicUrl:?string,fiscalReceiptEligibility:?bool,fiscalReceiptIssuanceEnabled:?bool,activityType:?string,organizationLogo:?string,organizationName:?string,placeCity:?string,placeZipCode:?string,description:?string,url:?string} $formData
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
            'fiscalReceiptIssuanceEnabled' => $formData['fiscalReceiptIssuanceEnabled'] ?? null,
            'category' => null
        ];
        $asso->fillFromApiData($apiData);
    }

    /**
     * ✅ MÉTHODE EXISTANTE AMÉLIORÉE : Verification des payers
     * @param array{firstName:?string,lastName:?string,address:?string,city:?string,zipCode:?string,country:?string,company:?string,email:?string} $data
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
     * @param array{firstName:?string,lastName:?string,address:?string,city:?string,zipCode:?string,country:?string,company:?string} $data
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
     * @param array{eventType:?string,data:?array{organizationName:?string,formSlug:?string,title:?string,payer:?array{firstName:?string,lastName:?string,email:?string}}} $data
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
     * @param array{eventType:?string,data:?array{organizationName:?string,formSlug:?string,title:?string,payer:?array{firstName:?string,lastName:?string,email:?string}}} $data
     */
    private function generateEmailHtml(array $data): string
    {
        $eventType = $data['eventType'] ?? 'Unknown';
        $timestamp = date('d/m/Y H:i:s');
        $formData = $data['data'] ?? [];
        
        $html = "<h2>Notification HelloAsso - " . htmlspecialchars($eventType, ENT_QUOTES, 'UTF-8') . "</h2>";
        $html .= "<p><strong>Reçue le :</strong> " . htmlspecialchars($timestamp, ENT_QUOTES, 'UTF-8') . "</p>";
        
        if (isset($formData['organizationName'])) {
            $html .= "<p><strong>Organisation :</strong> " . htmlspecialchars((string) $formData['organizationName'], ENT_QUOTES, 'UTF-8') . "</p>";
        }
        
        if (isset($formData['formSlug'])) {
            $html .= "<p><strong>Formulaire :</strong> " . htmlspecialchars((string) $formData['formSlug'], ENT_QUOTES, 'UTF-8') . "</p>";
        }
        
        if (isset($formData['title'])) {
            $html .= "<p><strong>Titre :</strong> " . htmlspecialchars((string) $formData['title'], ENT_QUOTES, 'UTF-8') . "</p>";
        }

        if (isset($formData['payer'])) {
            $payer = $formData['payer'];
            $html .= "<h3>Informations du payeur :</h3>";
            $firstName = htmlspecialchars((string) ($payer['firstName'] ?? ''), ENT_QUOTES, 'UTF-8');
            $lastName = htmlspecialchars((string) ($payer['lastName'] ?? ''), ENT_QUOTES, 'UTF-8');
            $payerEmail = htmlspecialchars((string) ($payer['email'] ?? ''), ENT_QUOTES, 'UTF-8');
            $html .= "<p><strong>Nom :</strong> {$firstName} {$lastName}</p>";
            $html .= "<p><strong>Email :</strong> {$payerEmail}</p>";
        }

        $html .= "<hr><h3>Données complètes :</h3>";
        $html .= "<pre>" . htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT)) . "</pre>";
        
        return $html;
    }

    /**
     * ✅ GÉNÉRATION DU CONTENU TEXTE DE L'EMAIL
     * @param array{eventType:?string,data:?array{organizationName:?string,formSlug:?string,title:?string,payer:?array{firstName:?string,lastName:?string,email:?string}}} $data
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
    #[IsGranted('ROLE_ADMIN')]
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
    #[IsGranted('ROLE_ADMIN')]
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
            $this->logger->error('Unable to load notification stats', [
                'error' => $e->getMessage(),
            ]);

            return new JsonResponse([
                'status' => 'error',
                'message' => 'Unable to load notification stats'
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
    
        /**
     * 🐛 DEBUG: Voir toutes les données créées
     */
    #[Route('/notification/debug-all', name: 'notification_debug_all', methods: ['GET'])]
    #[IsGranted('ROLE_ADMIN')]
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
