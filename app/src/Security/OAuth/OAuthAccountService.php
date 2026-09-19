<?php

namespace App\Security\OAuth;

use App\Entity\User;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

final class OAuthAccountService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function login(OAuthIdentity $identity): User
    {
        $linkedUser = $this->findCanonicalIdentity($identity);
        if ($linkedUser instanceof User) {
            return $linkedUser;
        }

        if ($identity->provider === OAuthProvider::Microsoft && ($legacyUser = $this->findLegacyMicrosoftIdentity($identity)) instanceof User) {
            throw new LegacyMicrosoftRepairRequired($legacyUser, $identity);
        }

        $email = $this->emailRequiredForCreation($identity);
        if ($this->entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
            throw new OAuthAccountException('Un compte existe déjà avec cette adresse email. Connectez-vous d’abord, puis liez ce fournisseur depuis votre profil.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
        $user->setVerified($identity->provider === OAuthProvider::Google && $identity->emailVerified);
        $this->applyIdentity($user, $identity);
        $this->entityManager->persist($user);
        $this->flushWithConflictMessage();

        return $user;
    }

    public function assertCanLink(User $user, OAuthIdentity $identity): void
    {
        $owner = $this->findCanonicalIdentity($identity);
        if ($owner instanceof User && $owner->getId() !== $user->getId()) {
            throw new OAuthAccountException('Cette identité OAuth est déjà liée à un autre compte.');
        }

        if ($identity->provider === OAuthProvider::Microsoft && ($legacyOwner = $this->findLegacyMicrosoftIdentity($identity)) instanceof User && $legacyOwner->getId() !== $user->getId()) {
            throw new OAuthAccountException('Cette ancienne identité Microsoft doit être réparée par son titulaire.');
        }

        if ($identity->provider === OAuthProvider::Google) {
            $this->assertVerifiedGoogleEmail($identity);
        }
    }

    public function assertIdentityBelongsTo(User $user, OAuthIdentity $identity): void
    {
        $owner = $this->findCanonicalIdentity($identity);
        if (!$owner instanceof User || $owner->getId() !== $user->getId()) {
            throw new OAuthAccountException('Cette identité OAuth n’est pas liée à votre compte.');
        }
    }

    public function confirmLink(User $user, OAuthIdentity $identity): void
    {
        $this->assertCanLink($user, $identity);
        $this->applyIdentity($user, $identity);
        $this->flushWithConflictMessage();
    }

    public function confirmLegacyMicrosoftRepair(User $user, OAuthIdentity $identity): void
    {
        if ($identity->provider !== OAuthProvider::Microsoft
            || $user->getAzureId() !== $identity->subject
            || $user->getAzureTenantId() !== null
            || $this->findCanonicalIdentity($identity) instanceof User
        ) {
            throw new OAuthAccountException('Cette demande de réparation Microsoft n’est plus valide.');
        }

        $user->setAzureTenantId($identity->tenantId);
        $this->flushWithConflictMessage();
    }

    private function findCanonicalIdentity(OAuthIdentity $identity): ?User
    {
        $repository = $this->entityManager->getRepository(User::class);
        if ($identity->provider === OAuthProvider::Google) {
            return $repository->findOneBy(['googleId' => $identity->subject]);
        }

        return $repository->findOneBy(['azureId' => $identity->subject, 'azureTenantId' => $identity->tenantId]);
    }

    private function findLegacyMicrosoftIdentity(OAuthIdentity $identity): ?User
    {
        $users = $this->entityManager->getRepository(User::class)->findBy([
            'azureId' => $identity->subject,
            'azureTenantId' => null,
        ], limit: 2);
        if (count($users) > 1) {
            throw new OAuthAccountException('Cette ancienne identité Microsoft doit être traitée par le support.');
        }
        return $users[0] ?? null;
    }

    private function emailRequiredForCreation(OAuthIdentity $identity): string
    {
        if (!is_string($identity->email) || filter_var($identity->email, FILTER_VALIDATE_EMAIL) === false) {
            throw new OAuthAccountException($identity->provider === OAuthProvider::Microsoft
                ? 'Microsoft n’a pas fourni d’adresse email utilisable pour créer un compte.'
                : 'Google n’a pas fourni d’adresse email utilisable.');
        }
        if ($identity->provider === OAuthProvider::Google) {
            $this->assertVerifiedGoogleEmail($identity);
        }
        return $identity->email;
    }

    private function assertVerifiedGoogleEmail(OAuthIdentity $identity): void
    {
        if (!is_string($identity->email) || filter_var($identity->email, FILTER_VALIDATE_EMAIL) === false || !$identity->emailVerified) {
            throw new OAuthAccountException('Google n’a pas confirmé l’adresse email de ce compte.');
        }
    }

    private function applyIdentity(User $user, OAuthIdentity $identity): void
    {
        if ($identity->provider === OAuthProvider::Google) {
            $user->setGoogleId($identity->subject);
            return;
        }

        $user->setAzureId($identity->subject);
        $user->setAzureTenantId($identity->tenantId);
    }

    private function flushWithConflictMessage(): void
    {
        try {
            $this->entityManager->flush();
        } catch (UniqueConstraintViolationException $exception) {
            throw new OAuthAccountException('Cette identité OAuth est déjà liée à un autre compte.', previous: $exception);
        }
    }
}
