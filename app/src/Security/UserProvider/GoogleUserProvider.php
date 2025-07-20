<?php

namespace App\Security\UserProvider;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use KnpU\OAuth2ClientBundle\Security\User\OAuthUserProviderInterface;
use League\OAuth2\Client\Provider\ResourceOwnerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class GoogleUserProvider implements OAuthUserProviderInterface, UserProviderInterface
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function loadUserByOAuthUserResponse(ResourceOwnerInterface $response)
    {
        $email = $response->getEmail();
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if (!$user) {
            $user = new User();
            $user->setEmail($email);
            $user->setRoles(["ROLE_USER"]);
            $this->em->persist($user);
            $this->em->flush();
        }
        return $user;
    }

    public function loadUserByUsername($username)
    {
        return $this->em->getRepository(User::class)->findOneBy(['email' => $username]);
    }

    public function refreshUser(UserInterface $user)
    {
        return $this->em->getRepository(User::class)->find($user->getId());
    }

    public function supportsClass($class)
    {
        return User::class === $class;
    }
}
