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

        $email = $this->emailRequiredForCreation($identity);
        if ($this->entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
            throw new OAuthAccountException('Un compte existe déjà avec cette adresse email. Connectez-vous d’abord, puis liez ce fournisseur depuis votre profil.');
        }

        $user = new User();
        $user->setEmail($email);
        $user->setPassword($this->passwordHasher->hashPassword($user, bin2hex(random_bytes(32))));
        $user->setVerified(OAuthProvider::Google === $identity->provider && $identity->emailVerified);
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

        if (OAuthProvider::Google === $identity->provider) {
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

    private function findCanonicalIdentity(OAuthIdentity $identity): ?User
    {
        $repository = $this->entityManager->getRepository(User::class);
        if (OAuthProvider::Google === $identity->provider) {
            return $repository->findOneBy(['googleId' => $identity->subject]);
        }

        return $repository->findOneBy(['azureId' => $identity->subject]);
    }

    private function emailRequiredForCreation(OAuthIdentity $identity): string
    {
        if (!is_string($identity->email) || false === filter_var($identity->email, FILTER_VALIDATE_EMAIL)) {
            throw new OAuthAccountException(OAuthProvider::Microsoft === $identity->provider ? 'Microsoft n’a pas fourni d’adresse email utilisable pour créer un compte.' : 'Google n’a pas fourni d’adresse email utilisable.');
        }
        if (OAuthProvider::Google === $identity->provider) {
            $this->assertVerifiedGoogleEmail($identity);
        }

        return $identity->email;
    }

    private function assertVerifiedGoogleEmail(OAuthIdentity $identity): void
    {
        if (!is_string($identity->email) || false === filter_var($identity->email, FILTER_VALIDATE_EMAIL) || !$identity->emailVerified) {
            throw new OAuthAccountException('Google n’a pas confirmé l’adresse email de ce compte.');
        }
    }

    private function applyIdentity(User $user, OAuthIdentity $identity): void
    {
        if (OAuthProvider::Google === $identity->provider) {
            $user->setGoogleId($identity->subject);

            return;
        }

        $user->setAzureId($identity->subject);
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
