<?php

namespace App\Service;

use App\Entity\AssoRecommander;
use Doctrine\ORM\EntityManagerInterface;

class AssoRecommanderService
{
    private HelloAssoApiService $helloAssoApiService;
    private EntityManagerInterface $entityManager;

    public function __construct(HelloAssoApiService $helloAssoApiService, EntityManagerInterface $entityManager)
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->entityManager = $entityManager;
    }

    public function createdAssoRecommanderFromApi(string $organizationSlug): ?AssoRecommander
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}";
        $data = $this->helloAssoApiService->makeApiCall($url);

        if ($data) {
            $assoRecommander = new AssoRecommander();
            $assoRecommander->setCreatedAt(new \DateTimeImmutable());
            $assoRecommander->setUpdatedAt(new \DateTime());
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

    /**
     * @param AssoRecommander $assoRecommander
     * @return AssoRecommander|string
     */
    public function updateAssoRecommanderFromApi(AssoRecommander $assoRecommander): AssoRecommander|string
    {
        // $assos = $this->entityManager->getRepository(AssoRecommander::class)->findAll();

        // foreach ($assos as $asso) {
        $url = "https://api.helloasso.com/v5/organizations/{$assoRecommander->getOrganizationSlug()}";
        $data = $this->helloAssoApiService->makeApiCall($url);

        // $assoRecommander = $asso;
        if ($data) {
            if (true === $this->isDataChanged($assoRecommander, $data)) {
                $assoRecommander->fillFromApiData($data);
            }
            $assoRecommander->setUpdatedAt(new \DateTime());

            // Ici, vous pouvez persister $assoRecommander avec EntityManager si nécessaire
            // dd($assoRecommander);
            $this->entityManager->persist($assoRecommander);
            $this->entityManager->flush();

            return $assoRecommander;
        } elseif (false === $this->isDataChanged($assoRecommander, $data)) {
            return 'Pas de mise à jour requis pour le moment';
        } else {
            return 'Pas de données à changer';
        }
    }

    /**
     * @param AssoRecommander $assoRecommander
     * @param array $data
     * @return bool|string
     */
    public function isDataChanged(AssoRecommander $assoRecommander, array $data): bool|string
    {
        try {
            if (count($data) > 0) {
                if ($assoRecommander->getName() !== $data['name'] || $assoRecommander->getDescription() !== $data['description'] || $assoRecommander->getBanner() !== $data['banner'] || $assoRecommander->getUrl() !== $data['url'] || $assoRecommander->getLogo() !== $data['logo'] || $assoRecommander->isFiscalReceiptEligibility() !== $data['fiscalReceiptEligibility'] || $assoRecommander->isFiscalReceiptIssuanceEnabled() !== $data['fiscalReceiptIssuanceEnabled'] || $assoRecommander->getType() !== $data['type'] || $assoRecommander->getCategory() !== $data['category']) {
                    return true;
                } else {
                    return false;
                }
            } else {
                return 'Pas de données dans le tableau ';
            }
        } catch (\Exception $e) {
            throw new \RuntimeException('Erreur lors de la comparaison des données : '.$e->getMessage());
        }
    }
}
