<?php
namespace App\Entity;

use App\Entity\Value\Meta;
use App\Repository\HelloAssoFormNotificationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: HelloAssoFormNotificationRepository::class)]
#[ORM\Table(name: 'helloasso_form_notification')]
#[ORM\Index(name: 'idx_helloasso_form_start_date', columns: ['start_date'])]
#[ORM\Index(name: 'idx_helloasso_form_org_slug', columns: ['organization_slug'])]
#[ORM\Index(name: 'idx_helloasso_form_slug', columns: ['form_slug'])]
#[ORM\HasLifecycleCallbacks]
class HelloAssoFormNotification
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private ?Uuid $id = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $eventType = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $organizationName = null;

    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $organizationLogo = null;

    #[ORM\Column(type: Types::STRING, length: 150, nullable: true)]
    private ?string $organizationSlug = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $activityType = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $activityTypeId = null;

    #[ORM\Column(type: Types::STRING, length: 3, nullable: true)]
    private ?string $currency = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $startDate = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $endDate = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $state = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $widgetButtonUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $widgetFullUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $widgetVignetteHorizontalUrl = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $widgetVignetteVerticalUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $formSlug = null;

    #[ORM\Column(type: Types::STRING, length: 50, nullable: true)]
    private ?string $formType = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $placeAddress = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $placeName = null;

    #[ORM\Column(type: Types::STRING, length: 120, nullable: true)]
    private ?string $placeCity = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $placeZipCode = null;

    #[ORM\Column(type: Types::STRING, length: 3, nullable: true)]
    private ?string $placeCountry = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $bannerFileName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $bannerPublicUrl = null;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $logoFileName = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $logoPublicUrl = null;

    #[ORM\Embedded(class: Meta::class)]
    private Meta $meta;

    // ✅ NOUVELLE RELATION : Collection des tiers HelloAsso
    #[ORM\OneToMany(mappedBy: 'form', targetEntity: HelloAssoTier::class, cascade: ['persist', 'remove'])]
    private Collection $tiers;

    public function __construct()
    {
        $this->id = Uuid::v4();
        $this->meta = new Meta();
        $this->tiers = new ArrayCollection(); // ✅ Initialiser la collection
    }

    /**
     * ✅ MÉTHODE STATIQUE : Créer une entité depuis un payload HelloAsso
     */
    public static function fromHelloAssoPayload(array $payload): self
    {
        $entity = new self();
        
        $entity->setEventType($payload['eventType'] ?? null);
        
        if (isset($payload['data'])) {
            $data = $payload['data'];
            
            $entity->setFormSlug($data['formSlug'] ?? null);
            $entity->setFormType($data['formType'] ?? null);
            $entity->setTitle($data['title'] ?? null);
            $entity->setDescription($data['description'] ?? null);
            $entity->setUrl($data['url'] ?? null);
            $entity->setState($data['state'] ?? null);
            $entity->setCurrency($data['currency'] ?? null);
            
            $entity->setOrganizationSlug($data['organizationSlug'] ?? null);
            $entity->setOrganizationName($data['organizationName'] ?? null);
            $entity->setOrganizationLogo($data['organizationLogo'] ?? null);
            $entity->setActivityType($data['activityType'] ?? null);
            $entity->setActivityTypeId($data['activityTypeId'] ?? null);
            
            if (isset($data['startDate'])) {
                try {
                    $entity->setStartDate(new \DateTimeImmutable($data['startDate']));
                } catch (\Exception $e) {
                    // Ignorer les erreurs de date
                }
            }
            if (isset($data['endDate'])) {
                try {
                    $entity->setEndDate(new \DateTimeImmutable($data['endDate']));
                } catch (\Exception $e) {
                    // Ignorer les erreurs de date
                }
            }
            
            if (isset($data['banner'])) {
                $entity->setBannerFileName($data['banner']['fileName'] ?? null);
                $entity->setBannerPublicUrl($data['banner']['publicUrl'] ?? null);
            }
            
            if (isset($data['logo'])) {
                $entity->setLogoFileName($data['logo']['fileName'] ?? null);
                $entity->setLogoPublicUrl($data['logo']['publicUrl'] ?? null);
            }
            
            if (isset($data['place'])) {
                $place = $data['place'];
                $entity->setPlaceName($place['name'] ?? null);
                $entity->setPlaceAddress($place['address'] ?? null);
                $entity->setPlaceCity($place['city'] ?? null);
                $entity->setPlaceZipCode($place['zipCode'] ?? null);
                $entity->setPlaceCountry($place['country'] ?? null);
            }
            
            if (isset($data['widget'])) {
                $widget = $data['widget'];
                $entity->setWidgetButtonUrl($widget['buttonUrl'] ?? null);
                $entity->setWidgetFullUrl($widget['fullUrl'] ?? null);
                $entity->setWidgetVignetteHorizontalUrl($widget['vignetteHorizontalUrl'] ?? null);
                $entity->setWidgetVignetteVerticalUrl($widget['vignetteVerticalUrl'] ?? null);
            }

            // ✅ TRAITEMENT DES TIERS si présents dans le payload
            if (isset($data['tiers']) && is_array($data['tiers'])) {
                foreach ($data['tiers'] as $tierData) {
                    $tier = HelloAssoTier::fromArray($tierData);
                    $entity->addTier($tier);
                }
            }
        }
        
        $entity->getMeta()->touch();
        
        return $entity;
    }

    // ✅ MÉTHODES POUR GÉRER LA COLLECTION DE TIERS
    /**
     * @return Collection<int, HelloAssoTier>
     */
    public function getTiers(): Collection
    {
        return $this->tiers;
    }

    public function addTier(HelloAssoTier $tier): self
    {
        if (!$this->tiers->contains($tier)) {
            $this->tiers->add($tier);
            $tier->setForm($this);
        }

        return $this;
    }

    public function removeTier(HelloAssoTier $tier): self
    {
        if ($this->tiers->removeElement($tier)) {
            // Set the owning side to null (unless already changed)
            if ($tier->getForm() === $this) {
                $tier->setForm(null);
            }
        }

        return $this;
    }

    // ✅ GETTERS ET SETTERS
    public function getId(): ?Uuid { return $this->id; }

    public function getEventType(): ?string { return $this->eventType; }
    public function setEventType(?string $eventType): self { $this->eventType = $eventType; return $this; }

    public function getOrganizationName(): ?string { return $this->organizationName; }
    public function setOrganizationName(?string $organizationName): self { $this->organizationName = $organizationName; return $this; }

    public function getOrganizationLogo(): ?string { return $this->organizationLogo; }
    public function setOrganizationLogo(?string $organizationLogo): self { $this->organizationLogo = $organizationLogo; return $this; }

    public function getOrganizationSlug(): ?string { return $this->organizationSlug; }
    public function setOrganizationSlug(?string $organizationSlug): self { $this->organizationSlug = $organizationSlug; return $this; }

    public function getFormSlug(): ?string { return $this->formSlug; }
    public function setFormSlug(?string $formSlug): self { $this->formSlug = $formSlug; return $this; }

    public function getFormType(): ?string { return $this->formType; }
    public function setFormType(?string $formType): self { $this->formType = $formType; return $this; }

    public function getTitle(): ?string { return $this->title; }
    public function setTitle(?string $title): self { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): self { $this->url = $url; return $this; }

    public function getState(): ?string { return $this->state; }
    public function setState(?string $state): self { $this->state = $state; return $this; }

    public function getCurrency(): ?string { return $this->currency; }
    public function setCurrency(?string $currency): self { $this->currency = $currency; return $this; }

    public function getActivityType(): ?string { return $this->activityType; }
    public function setActivityType(?string $activityType): self { $this->activityType = $activityType; return $this; }

    public function getActivityTypeId(): ?int { return $this->activityTypeId; }
    public function setActivityTypeId(?int $activityTypeId): self { $this->activityTypeId = $activityTypeId; return $this; }

    public function getStartDate(): ?\DateTimeImmutable { return $this->startDate; }
    public function setStartDate(?\DateTimeImmutable $startDate): self { $this->startDate = $startDate; return $this; }

    public function getEndDate(): ?\DateTimeImmutable { return $this->endDate; }
    public function setEndDate(?\DateTimeImmutable $endDate): self { $this->endDate = $endDate; return $this; }

    public function getBannerFileName(): ?string { return $this->bannerFileName; }
    public function setBannerFileName(?string $bannerFileName): self { $this->bannerFileName = $bannerFileName; return $this; }

    public function getBannerPublicUrl(): ?string { return $this->bannerPublicUrl; }
    public function setBannerPublicUrl(?string $bannerPublicUrl): self { $this->bannerPublicUrl = $bannerPublicUrl; return $this; }

    public function getLogoFileName(): ?string { return $this->logoFileName; }
    public function setLogoFileName(?string $logoFileName): self { $this->logoFileName = $logoFileName; return $this; }

    public function getLogoPublicUrl(): ?string { return $this->logoPublicUrl; }
    public function setLogoPublicUrl(?string $logoPublicUrl): self { $this->logoPublicUrl = $logoPublicUrl; return $this; }

    public function getPlaceName(): ?string { return $this->placeName; }
    public function setPlaceName(?string $placeName): self { $this->placeName = $placeName; return $this; }

    public function getPlaceAddress(): ?string { return $this->placeAddress; }
    public function setPlaceAddress(?string $placeAddress): self { $this->placeAddress = $placeAddress; return $this; }

    public function getPlaceCity(): ?string { return $this->placeCity; }
    public function setPlaceCity(?string $placeCity): self { $this->placeCity = $placeCity; return $this; }

    public function getPlaceZipCode(): ?string { return $this->placeZipCode; }
    public function setPlaceZipCode(?string $placeZipCode): self { $this->placeZipCode = $placeZipCode; return $this; }

    public function getPlaceCountry(): ?string { return $this->placeCountry; }
    public function setPlaceCountry(?string $placeCountry): self { $this->placeCountry = $placeCountry; return $this; }

    public function getWidgetButtonUrl(): ?string { return $this->widgetButtonUrl; }
    public function setWidgetButtonUrl(?string $widgetButtonUrl): self { $this->widgetButtonUrl = $widgetButtonUrl; return $this; }

    public function getWidgetFullUrl(): ?string { return $this->widgetFullUrl; }
    public function setWidgetFullUrl(?string $widgetFullUrl): self { $this->widgetFullUrl = $widgetFullUrl; return $this; }

    public function getWidgetVignetteHorizontalUrl(): ?string { return $this->widgetVignetteHorizontalUrl; }
    public function setWidgetVignetteHorizontalUrl(?string $widgetVignetteHorizontalUrl): self { $this->widgetVignetteHorizontalUrl = $widgetVignetteHorizontalUrl; return $this; }

    public function getWidgetVignetteVerticalUrl(): ?string { return $this->widgetVignetteVerticalUrl; }
    public function setWidgetVignetteVerticalUrl(?string $widgetVignetteVerticalUrl): self { $this->widgetVignetteVerticalUrl = $widgetVignetteVerticalUrl; return $this; }

    public function getMeta(): Meta { return $this->meta; }
    public function setMeta(Meta $meta): self { $this->meta = $meta; return $this; }

    // ✅ MÉTHODES UTILITAIRES SUPPLÉMENTAIRES
    public function getTierCount(): int
    {
        return $this->tiers->count();
    }

    public function hasActiveTiers(): bool
    {
        return !$this->tiers->isEmpty();
    }

    public function getFavoriteTiers(): Collection
    {
        return $this->tiers->filter(function(HelloAssoTier $tier) {
            return $tier->getIsFavorite() === true;
        });
    }

    public function getTiersByPriceRange(float $minPrice, float $maxPrice): Collection
    {
        return $this->tiers->filter(function(HelloAssoTier $tier) use ($minPrice, $maxPrice) {
            $price = $tier->getPriceAsFloat();
            return $price !== null && $price >= $minPrice && $price <= $maxPrice;
        });
    }

    #[ORM\PreUpdate]
    public function updateTimestamp(): void
    {
        $this->meta->touch();
    }

    public function __toString(): string
    {
        return $this->title ?? $this->formSlug ?? 'HelloAsso Notification #' . $this->id;
    }
}