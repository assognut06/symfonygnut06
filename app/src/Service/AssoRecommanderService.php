<?php
namespace App\Service;

use App\Entity\AssoRecommander;
use Doctrine\ORM\EntityManagerInterface;

class AssoRecommanderService
{
    private $helloAssoApiService;
    private $entityManager;

    public function __construct(HelloAssoApiService $helloAssoApiService, EntityManagerInterface $entityManager)
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->entityManager = $entityManager;
    }

    public function updateAssoRecommanderFromApi(string $organizationSlug)
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}";
        $data = $this->helloAssoApiService->makeApiCall($url);

        if ($data) {
            $assoRecommander = new AssoRecommander();
            // Définir organizationSlug
            $assoRecommander->setOrganizationSlug($organizationSlug);
            $assoRecommander->fillFromApiData($data);
            // Ici, vous pouvez persister $assoRecommander avec EntityManager si nécessaire
            $this->entityManager->persist($assoRecommander);
            $this->entityManager->flush();

            return $assoRecommander;
        }

        return null;
    }
}