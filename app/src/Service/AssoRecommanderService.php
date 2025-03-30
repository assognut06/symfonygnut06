<?php
namespace App\Service;

use App\Entity\AssoRecommander;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use phpDocumentor\Reflection\Types\Boolean;

class AssoRecommanderService
{
    private $helloAssoApiService;
    private $entityManager;

    public function __construct(HelloAssoApiService $helloAssoApiService, EntityManagerInterface $entityManager)
    {
        $this->helloAssoApiService = $helloAssoApiService;
        $this->entityManager = $entityManager;
    }

    public function createdAssoRecommanderFromApi(string $organizationSlug)
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

    public function updateAssoRecommanderFromApi(AssoRecommander $assoRecommander) 
    {
        // $assos = $this->entityManager->getRepository(AssoRecommander::class)->findAll();

        // foreach ($assos as $asso) {
            $url = "https://api.helloasso.com/v5/organizations/{$assoRecommander->getOrganizationSlug()}";
            $data = $this->helloAssoApiService->makeApiCall($url);

            // $assoRecommander = $asso;
            if ($data) {
                if($this->isDataChanged($assoRecommander, $data) === true)
                $assoRecommander->fillFromApiData($data);
                $assoRecommander->setUpdatedAt(new \DateTime());
               
                // Ici, vous pouvez persister $assoRecommander avec EntityManager si nécessaire
                // dd($assoRecommander);
                $this->entityManager->persist($assoRecommander);
                $this->entityManager->flush();
                return $assoRecommander;
            } else  if ($this->isDataChanged($assoRecommander, $data) === false) {
                return "Pas de mise à jour requis pour le moment";
            } else {
                return "Pas de données à changés";
            }
        // }


        return null;
    }

    public function isDataChanged(AssoRecommander $assoRecommander, array $data) 
    {
        try{
            if(count($data) > 0 ){
                if($assoRecommander->getName() !== $data["name"] || $assoRecommander->getDescription() !== $data["description"] || $assoRecommander->getBanner() !== $data["banner"] || $assoRecommander->getUrl() !== $data["url"] || $assoRecommander->getLogo() !== $data["logo"] || $assoRecommander->isFiscalReceiptEligibility() !== $data["fiscalReceiptEligibility"] || $assoRecommander->isFiscalReceiptIssuanceEnabled() !== $data["fiscalReceiptIssuanceEnabled"] || $assoRecommander->getType() !== $data["type"] || $assoRecommander->getCategory() !== $data["category"]) {
                    return true;
                } else {
                    return false;
                }

            } else {
                return "Pas de données dans le tableau ".$data;
            }
        } catch(Exception $e) {
            return $e;
        }
            
    }
    
}
