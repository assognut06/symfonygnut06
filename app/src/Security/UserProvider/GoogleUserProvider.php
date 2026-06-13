<?php

declare(strict_types=1);

namespace App\Security\UserProvider;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class GoogleUserProvider implements UserProviderInterface
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        // Ici, $identifier = email (si tu l'utilises comme identifiant)
        return $this->em->getRepository(User::class)->findOneBy(['email' => $identifier]);
    }

    public function loadUserByUsername(string $username): ?UserInterface
    {
        return $this->loadUserByIdentifier($username);
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Unsupported user class "%s".', $user::class));
        }

        $reloaded = $this->em->getRepository(User::class)->find($user->getId());

        if (!$reloaded instanceof User) {
            // user supprimé / plus trouvable
            throw new UnsupportedUserException('User could not be reloaded from storage.');
        }

        return $reloaded;
    }

    public function supportsClass(string $class): bool
    {
        return $class === User::class || is_subclass_of($class, User::class);
    }
}