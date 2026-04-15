<?php

namespace App\DataFixtures;

use App\Entity\Tih;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class TihFixtures extends Fixture implements DependentFixtureInterface
{
    private const RATE_TYPES = ['Horaire', 'Journalier'];
    
    private const CITIES = [
        // Île-de-France
        ['name' => 'Paris', 'postalCode' => '75001', 'departement' => 'Paris', 'region' => 'Île-de-France'],
        ['name' => 'Versailles', 'postalCode' => '78000', 'departement' => 'Yvelines', 'region' => 'Île-de-France'],
        ['name' => 'Boulogne-Billancourt', 'postalCode' => '92100', 'departement' => 'Hauts-de-Seine', 'region' => 'Île-de-France'],
        ['name' => 'Nanterre', 'postalCode' => '92000', 'departement' => 'Hauts-de-Seine', 'region' => 'Île-de-France'],
        ['name' => 'Créteil', 'postalCode' => '94000', 'departement' => 'Val-de-Marne', 'region' => 'Île-de-France'],
        ['name' => 'Évry', 'postalCode' => '91000', 'departement' => 'Essonne', 'region' => 'Île-de-France'],
        ['name' => 'Cergy', 'postalCode' => '95000', 'departement' => 'Val-d\'Oise', 'region' => 'Île-de-France'],
        ['name' => 'Meaux', 'postalCode' => '77100', 'departement' => 'Seine-et-Marne', 'region' => 'Île-de-France'],
        
        // Provence-Alpes-Côte d'Azur
        ['name' => 'Marseille', 'postalCode' => '13001', 'departement' => 'Bouches-du-Rhône', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        ['name' => 'Nice', 'postalCode' => '06000', 'departement' => 'Alpes-Maritimes', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        ['name' => 'Cannes', 'postalCode' => '06400', 'departement' => 'Alpes-Maritimes', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        ['name' => 'Antibes', 'postalCode' => '06600', 'departement' => 'Alpes-Maritimes', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        ['name' => 'Toulon', 'postalCode' => '83000', 'departement' => 'Var', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        ['name' => 'Aix-en-Provence', 'postalCode' => '13100', 'departement' => 'Bouches-du-Rhône', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        ['name' => 'Avignon', 'postalCode' => '84000', 'departement' => 'Vaucluse', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        ['name' => 'Grasse', 'postalCode' => '06130', 'departement' => 'Alpes-Maritimes', 'region' => 'Provence-Alpes-Côte d\'Azur'],
        
        // Auvergne-Rhône-Alpes
        ['name' => 'Lyon', 'postalCode' => '69001', 'departement' => 'Rhône', 'region' => 'Auvergne-Rhône-Alpes'],
        ['name' => 'Grenoble', 'postalCode' => '38000', 'departement' => 'Isère', 'region' => 'Auvergne-Rhône-Alpes'],
        ['name' => 'Saint-Étienne', 'postalCode' => '42000', 'departement' => 'Loire', 'region' => 'Auvergne-Rhône-Alpes'],
        ['name' => 'Annecy', 'postalCode' => '74000', 'departement' => 'Haute-Savoie', 'region' => 'Auvergne-Rhône-Alpes'],
        ['name' => 'Chambéry', 'postalCode' => '73000', 'departement' => 'Savoie', 'region' => 'Auvergne-Rhône-Alpes'],
        ['name' => 'Valence', 'postalCode' => '26000', 'departement' => 'Drôme', 'region' => 'Auvergne-Rhône-Alpes'],
        ['name' => 'Clermont-Ferrand', 'postalCode' => '63000', 'departement' => 'Puy-de-Dôme', 'region' => 'Auvergne-Rhône-Alpes'],
        
        // Occitanie
        ['name' => 'Toulouse', 'postalCode' => '31000', 'departement' => 'Haute-Garonne', 'region' => 'Occitanie'],
        ['name' => 'Montpellier', 'postalCode' => '34000', 'departement' => 'Hérault', 'region' => 'Occitanie'],
        ['name' => 'Nîmes', 'postalCode' => '30000', 'departement' => 'Gard', 'region' => 'Occitanie'],
        ['name' => 'Perpignan', 'postalCode' => '66000', 'departement' => 'Pyrénées-Orientales', 'region' => 'Occitanie'],
        ['name' => 'Béziers', 'postalCode' => '34500', 'departement' => 'Hérault', 'region' => 'Occitanie'],
        ['name' => 'Albi', 'postalCode' => '81000', 'departement' => 'Tarn', 'region' => 'Occitanie'],
        
        // Nouvelle-Aquitaine
        ['name' => 'Bordeaux', 'postalCode' => '33000', 'departement' => 'Gironde', 'region' => 'Nouvelle-Aquitaine'],
        ['name' => 'Limoges', 'postalCode' => '87000', 'departement' => 'Haute-Vienne', 'region' => 'Nouvelle-Aquitaine'],
        ['name' => 'Poitiers', 'postalCode' => '86000', 'departement' => 'Vienne', 'region' => 'Nouvelle-Aquitaine'],
        ['name' => 'La Rochelle', 'postalCode' => '17000', 'departement' => 'Charente-Maritime', 'region' => 'Nouvelle-Aquitaine'],
        ['name' => 'Pau', 'postalCode' => '64000', 'departement' => 'Pyrénées-Atlantiques', 'region' => 'Nouvelle-Aquitaine'],
        ['name' => 'Bayonne', 'postalCode' => '64100', 'departement' => 'Pyrénées-Atlantiques', 'region' => 'Nouvelle-Aquitaine'],
        
        // Pays de la Loire
        ['name' => 'Nantes', 'postalCode' => '44000', 'departement' => 'Loire-Atlantique', 'region' => 'Pays de la Loire'],
        ['name' => 'Angers', 'postalCode' => '49000', 'departement' => 'Maine-et-Loire', 'region' => 'Pays de la Loire'],
        ['name' => 'Le Mans', 'postalCode' => '72000', 'departement' => 'Sarthe', 'region' => 'Pays de la Loire'],
        ['name' => 'Saint-Nazaire', 'postalCode' => '44600', 'departement' => 'Loire-Atlantique', 'region' => 'Pays de la Loire'],
        
        // Bretagne
        ['name' => 'Rennes', 'postalCode' => '35000', 'departement' => 'Ille-et-Vilaine', 'region' => 'Bretagne'],
        ['name' => 'Brest', 'postalCode' => '29200', 'departement' => 'Finistère', 'region' => 'Bretagne'],
        ['name' => 'Quimper', 'postalCode' => '29000', 'departement' => 'Finistère', 'region' => 'Bretagne'],
        ['name' => 'Lorient', 'postalCode' => '56100', 'departement' => 'Morbihan', 'region' => 'Bretagne'],
        ['name' => 'Vannes', 'postalCode' => '56000', 'departement' => 'Morbihan', 'region' => 'Bretagne'],
        
        // Grand Est
        ['name' => 'Strasbourg', 'postalCode' => '67000', 'departement' => 'Bas-Rhin', 'region' => 'Grand Est'],
        ['name' => 'Reims', 'postalCode' => '51100', 'departement' => 'Marne', 'region' => 'Grand Est'],
        ['name' => 'Metz', 'postalCode' => '57000', 'departement' => 'Moselle', 'region' => 'Grand Est'],
        ['name' => 'Nancy', 'postalCode' => '54000', 'departement' => 'Meurthe-et-Moselle', 'region' => 'Grand Est'],
        ['name' => 'Mulhouse', 'postalCode' => '68100', 'departement' => 'Haut-Rhin', 'region' => 'Grand Est'],
        
        // Hauts-de-France
        ['name' => 'Lille', 'postalCode' => '59000', 'departement' => 'Nord', 'region' => 'Hauts-de-France'],
        ['name' => 'Amiens', 'postalCode' => '80000', 'departement' => 'Somme', 'region' => 'Hauts-de-France'],
        ['name' => 'Roubaix', 'postalCode' => '59100', 'departement' => 'Nord', 'region' => 'Hauts-de-France'],
        ['name' => 'Tourcoing', 'postalCode' => '59200', 'departement' => 'Nord', 'region' => 'Hauts-de-France'],
        ['name' => 'Dunkerque', 'postalCode' => '59140', 'departement' => 'Nord', 'region' => 'Hauts-de-France'],
        
        // Normandie
        ['name' => 'Rouen', 'postalCode' => '76000', 'departement' => 'Seine-Maritime', 'region' => 'Normandie'],
        ['name' => 'Le Havre', 'postalCode' => '76600', 'departement' => 'Seine-Maritime', 'region' => 'Normandie'],
        ['name' => 'Caen', 'postalCode' => '14000', 'departement' => 'Calvados', 'region' => 'Normandie'],
        ['name' => 'Cherbourg', 'postalCode' => '50100', 'departement' => 'Manche', 'region' => 'Normandie'],
        
        // Bourgogne-Franche-Comté
        ['name' => 'Dijon', 'postalCode' => '21000', 'departement' => 'Côte-d\'Or', 'region' => 'Bourgogne-Franche-Comté'],
        ['name' => 'Besançon', 'postalCode' => '25000', 'departement' => 'Doubs', 'region' => 'Bourgogne-Franche-Comté'],
        ['name' => 'Belfort', 'postalCode' => '90000', 'departement' => 'Territoire de Belfort', 'region' => 'Bourgogne-Franche-Comté'],
        
        // Centre-Val de Loire
        ['name' => 'Orléans', 'postalCode' => '45000', 'departement' => 'Loiret', 'region' => 'Centre-Val de Loire'],
        ['name' => 'Tours', 'postalCode' => '37000', 'departement' => 'Indre-et-Loire', 'region' => 'Centre-Val de Loire'],
        ['name' => 'Bourges', 'postalCode' => '18000', 'departement' => 'Cher', 'region' => 'Centre-Val de Loire'],
    ];

    private const AVAILABILITIES = ['Immédiate', 'Sous 15 jours', 'Sous 1 mois', 'Sous 3 mois'];

    private const FIRST_NAMES = ['Sophie', 'Pierre', 'Marie', 'Luc', 'Camille', 'Thomas', 'Julie', 'Marc', 'Laura', 'Nicolas', 'Emma', 'Alexandre', 'Clara', 'Vincent', 'Léa', 'Julien', 'Sarah', 'Antoine', 'Chloé', 'Maxime', 'Manon', 'Lucas', 'Inès', 'Hugo', 'Lucie', 'Louis', 'Zoé', 'Arthur', 'Alice', 'Gabriel', 'Jade', 'Raphaël', 'Louise', 'Nathan', 'Léna', 'Tom', 'Anaïs', 'Paul', 'Charlotte', 'Théo', 'Émilie', 'Baptiste', 'Mathilde', 'Hugo', 'Océane', 'Enzo', 'Pauline', 'Romain', 'Margaux', 'Valentin'];

    private const LAST_NAMES = ['Martin', 'Bernard', 'Dubois', 'Thomas', 'Robert', 'Richard', 'Petit', 'Durand', 'Leroy', 'Moreau', 'Simon', 'Laurent', 'Lefebvre', 'Michel', 'Garcia', 'David', 'Bertrand', 'Roux', 'Vincent', 'Fournier', 'Morel', 'Girard', 'Andre', 'Lefevre', 'Mercier', 'Dupont', 'Lambert', 'Bonnet', 'Francois', 'Martinez', 'Legrand', 'Garnier', 'Faure', 'Rousseau', 'Blanc', 'Guerin', 'Muller', 'Henry', 'Roussel', 'Nicolas', 'Perrin', 'Morin', 'Mathieu', 'Clement', 'Gauthier', 'Dumont', 'Lopez', 'Fontaine', 'Chevalier', 'Robin'];

    public function load(ObjectManager $manager): void
    {
        for ($i = 1; $i <= 100; $i++) {
            $user = $this->getReference(UserFixtures::USER_TIH_PREFIX . $i);
            
            $tih = new Tih();
            $tih->setUser($user);
            $tih->setFirstName(self::FIRST_NAMES[($i - 1) % count(self::FIRST_NAMES)]);
            $tih->setLastName(self::LAST_NAMES[($i - 1) % count(self::LAST_NAMES)]);
            
            $cityIndex = ($i - 1) % count(self::CITIES);
            $city = self::CITIES[$cityIndex];
            $tih->setCity($city['name']);
            $tih->setPostalCode($city['postalCode']);
            $tih->setRegion($city['region']);
            $tih->setDepartement($city['departement']);
            $tih->setAddress(sprintf('%d Rue de la République', rand(1, 150)));
            
            $tih->setProfessionalEmail(sprintf('%s.%s%d@freelance.com', 
                strtolower(self::FIRST_NAMES[($i - 1) % count(self::FIRST_NAMES)]), 
                strtolower(self::LAST_NAMES[($i - 1) % count(self::LAST_NAMES)]),
                $i
            ));
            $tih->setPhone(sprintf('06%08d', rand(10000000, 99999999)));
            
            $availIndex = ($i - 1) % count(self::AVAILABILITIES);
            $tih->setAvailability(self::AVAILABILITIES[$availIndex]);
            
            $numSkills = rand(2, 4);
            $totalCompetences = 15;
            $skillIndices = array_rand(range(0, $totalCompetences - 1), $numSkills);
            if (!is_array($skillIndices)) {
                $skillIndices = [$skillIndices];
            }
            
            foreach ($skillIndices as $skillIndex) {
                $competence = $this->getReference(CompetenceFixtures::COMPETENCE_PREFIX . $skillIndex);
                $tih->addCompetence($competence);
            }
            
            $rate = rand(30, 80);
            $tih->setRate((string)$rate);
            
            $rateType = self::RATE_TYPES[($i - 1) % count(self::RATE_TYPES)];
            $tih->setRateType($rateType);
            
            $daysFromNow = rand(0, 90);
            $availabilityDate = new \DateTime();
            $availabilityDate->modify("+{$daysFromNow} days");
            $tih->setAvailabilityDate($availabilityDate);
            
            $tih->setIsValidate(true);
            
            $createdAt = new \DateTime();
            $createdAt->modify('-' . rand(1, 60) . ' days');
            $tih->setCreatedAt($createdAt);
            
            $manager->persist($tih);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [UserFixtures::class, CompetenceFixtures::class];
    }
}
