<?php

namespace App\DataFixtures;

use App\Entity\Competence;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class CompetenceFixtures extends Fixture
{
    public const COMPETENCE_PREFIX = "competence_";
    
    private const COMPETENCES = [
        "Développement Web",
        "Développement Mobile",
        "Design Graphique",
        "Marketing Digital",
        "Rédaction Web",
        "Community Management",
        "Comptabilité",
        "Assistance Administrative",
        "Traduction",
        "Photographie",
        "Montage Vidéo",
        "SEO/SEA",
        "Data Analysis",
        "Consulting IT",
        "Gestion de Projet",
    ];

    public function load(ObjectManager $manager): void
    {
        foreach (self::COMPETENCES as $index => $competenceName) {
            $competence = new Competence();
            $competence->setName($competenceName);
            $manager->persist($competence);
            
            $this->addReference(self::COMPETENCE_PREFIX . $index, $competence);
        }

        $manager->flush();
    }
}
