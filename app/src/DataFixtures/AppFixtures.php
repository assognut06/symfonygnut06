<?php

namespace App\DataFixtures;

use App\Entity\Competence;
use App\Entity\Tih;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create competences first
        $competences = [];
        $competenceNames = [
            'Développement Web',
            'Design Graphique',
            'Marketing Digital',
            'Rédaction',
            'Comptabilité',
            'Traduction',
            'Photographie',
            'Développement Mobile',
            'Conseil',
            'Formation',
            'Gestion de Projet',
            'SEO/SEA',
            'Community Management',
            'Montage Vidéo',
            'Développement Python'
        ];

        foreach ($competenceNames as $name) {
            $competence = new Competence();
            $competence->setName($name);
            $manager->persist($competence);
            $competences[] = $competence;
        }

        // Create 30 TIH profiles for testing pagination (12 per page)
        $cities = [
            ['Nice', '06000'],
            ['Cannes', '06400'],
            ['Antibes', '06600'],
            ['Grasse', '06130'],
            ['Menton', '06500'],
            ['Cagnes-sur-Mer', '06800'],
            ['Saint-Laurent-du-Var', '06700'],
            ['Monaco', '98000'],
            ['Valbonne', '06560'],
            ['Mougins', '06250']
        ];

        $disponibilites = ['Disponible', 'Occupé', 'Partiellement disponible'];
        $civilites = ['M.', 'Mme', 'Mx'];

        $firstNames = [
            'Sophie', 'Pierre', 'Marie', 'Jean', 'Claire', 'Thomas', 
            'Julie', 'Marc', 'Laura', 'Julien', 'Emma', 'Nicolas',
            'Camille', 'Alexandre', 'Sarah', 'David', 'Léa', 'Antoine',
            'Chloé', 'Maxime', 'Alice', 'Hugo', 'Manon', 'Lucas',
            'Pauline', 'Romain', 'Emilie', 'Vincent', 'Marine', 'Mathieu'
        ];

        $lastNames = [
            'Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard',
            'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent',
            'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux',
            'Vincent', 'Fournier', 'Morel', 'Girard', 'André', 'Mercier',
            'Dupont', 'Lambert', 'Bonnet', 'François', 'Martinez', 'Legrand'
        ];

        for ($i = 1; $i <= 30; $i++) {
            $tih = new Tih();
            
            $firstName = $firstNames[$i - 1];
            $lastName = $lastNames[$i - 1];
            $city = $cities[($i - 1) % count($cities)];
            
            $tih->setCivilite($civilites[$i % count($civilites)]);
            $tih->setNom($lastName);
            $tih->setPrenom($firstName);
            $tih->setEmailPro(strtolower($firstName . '.' . $lastName . '@example.com'));
            $tih->setTelephone('06' . str_pad($i, 8, '0', STR_PAD_LEFT));
            $tih->setAdresse($i . ' Avenue de la République');
            $tih->setVille($city[0]);
            $tih->setCodePostal($city[1]);
            $tih->setDisponibilite($disponibilites[$i % count($disponibilites)]);
            $tih->setSiret('123456789' . str_pad($i, 5, '0', STR_PAD_LEFT));
            $tih->setIsValidate(true);
            
            // Set created and updated dates manually since PrePersist might need them
            $now = new \DateTimeImmutable();
            $tih->setCreatedAt($now);
            $tih->setUpdatedAt($now);
            
            // Add 2-4 random competences to each TIH
            $numCompetences = rand(2, 4);
            $randomCompetences = array_rand($competences, $numCompetences);
            if (!is_array($randomCompetences)) {
                $randomCompetences = [$randomCompetences];
            }
            
            foreach ($randomCompetences as $index) {
                $tih->addCompetence($competences[$index]);
            }
            
            $manager->persist($tih);
        }

        $manager->flush();
        
        echo "\n✅ Created 30 TIH profiles with " . count($competences) . " competences\n";
        echo "   - Cities: " . implode(', ', array_column($cities, 0)) . "\n";
        echo "   - Ready to test pagination (12 items per page = 3 pages)\n";
        echo "   - Ready to test search by name, city, email, competence\n\n";
    }
}
