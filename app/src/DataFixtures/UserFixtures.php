<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public const USER_TIH_PREFIX = "user_tih_";
    public const ADMIN_USER = "user_admin";

    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        $admin = new User();
        $admin->setEmail("admin@gnut06.org");
        $admin->setPassword($this->passwordHasher->hashPassword($admin, "admin123"));
        $admin->setRoles(["ROLE_ADMIN"]);
        $manager->persist($admin);
        $this->addReference(self::ADMIN_USER, $admin);

        for ($i = 1; $i <= 100; $i++) {
            $user = new User();
            $user->setEmail(sprintf("tih%d@example.com", $i));
            $user->setPassword($this->passwordHasher->hashPassword($user, "password123"));
            $user->setRoles(["ROLE_TIH"]);
            $manager->persist($user);
            
            $this->addReference(self::USER_TIH_PREFIX . $i, $user);
        }

        $manager->flush();
    }
}
