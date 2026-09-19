<?php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'microsoft_identity_repair')]
#[ORM\Index(name: 'IDX_MICROSOFT_REPAIR_TOKEN', columns: ['token_hash'])]
class MicrosoftIdentityRepair
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(length: 255)]
    private string $legacyAzureId;

    #[ORM\Column(length: 255)]
    private string $azureTenantId;

    #[ORM\Column(length: 255)]
    private string $azureObjectId;

    #[ORM\Column(length: 64)]
    private string $tokenHash;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $expiresAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?\DateTimeImmutable $consumedAt = null;

    public function __construct(User $user, string $legacyAzureId, string $azureTenantId, string $azureObjectId, string $tokenHash)
    {
        $this->user = $user;
        $this->legacyAzureId = $legacyAzureId;
        $this->azureTenantId = $azureTenantId;
        $this->azureObjectId = $azureObjectId;
        $this->tokenHash = $tokenHash;
        $this->expiresAt = new \DateTimeImmutable('+10 minutes');
    }

    public function getUser(): User { return $this->user; }
    public function getLegacyAzureId(): string { return $this->legacyAzureId; }
    public function getAzureTenantId(): string { return $this->azureTenantId; }
    public function getAzureObjectId(): string { return $this->azureObjectId; }
    public function getTokenHash(): string { return $this->tokenHash; }

    public function canBeConsumed(): bool
    {
        return $this->consumedAt === null && $this->expiresAt >= new \DateTimeImmutable();
    }

    public function consume(): void
    {
        $this->consumedAt = new \DateTimeImmutable();
    }
}
