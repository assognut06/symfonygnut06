<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // This file is kept for compatibility
        // Real fixtures are in specific files: UserFixtures, CompetenceFixtures, TihFixtures
    }
}
