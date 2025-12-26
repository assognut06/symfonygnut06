<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Main fixtures file - use specific fixtures classes for each entity
        // See CompetenceFixtures.php and TihFixtures.php
        
        $manager->flush();
    }
}
