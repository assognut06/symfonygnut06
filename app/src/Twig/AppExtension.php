<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Service\HelloAssoApiService;
use App\Service\DataFilterAndPaginator;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    private $parameterBag;
    private $helloAssoApiService;
    private $dataFilterAndPaginator;

    public function __construct(ParameterBagInterface $parameterBag, HelloAssoApiService $helloAssoApiService, DataFilterAndPaginator $dataFilterAndPaginator)
    {
        $this->parameterBag = $parameterBag;
        $this->helloAssoApiService = $helloAssoApiService;
        $this->dataFilterAndPaginator = $dataFilterAndPaginator;
    }
    public function getFilters()
    {
        return [
            new TwigFilter('url_decode', [$this, 'urlDecode']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('formattedPrice', [$this, 'formatPrice']),
            new TwigFunction('randomImage', [$this, 'randomImage']),
            new TwigFunction('getEventsAssoRecommender', [$this, 'getEventsAssoRecommender']),
        ];
    }

    public function formatPrice($number): string
    {
        // Convertir les centimes en euros avec une séparation pour les décimales
        $priceInEuros = $number / 100;

        // Formater le nombre pour séparer les milliers et retirer le séparateur décimal
        $formattedPrice = number_format($priceInEuros, 2, ',', ' ');

        // Remplacer la virgule par l'euro et ajuster pour le format voulu
        // Ici, on prend la partie entière, on ajoute '€' et on append les deux derniers chiffres
        $formattedPrice = substr($formattedPrice, 0, -3) . '€' . substr($formattedPrice, -2);
        return $formattedPrice;
    }


    public function randomImage($directory)
    {
        // Utiliser le répertoire racine du projet pour construire le chemin absolu
        $projectDir = $this->parameterBag->get('kernel.project_dir');
        $absolutePath = $projectDir . '/public' . $directory;

        $finder = new Finder();
        try {
            $finder->files()->in($absolutePath);
        } catch (\Exception $e) {
            // Gérer l'exception si le répertoire n'existe pas
            // Par exemple, retourner un chemin d'image par défaut
            return '/public/images/news/1714486898185.jpeg';
        }

        $files = iterator_to_array($finder);
        if (count($files) > 0) {
            $randomFile = $files[array_rand($files)];
            return $directory . $randomFile->getRelativePathname();
        }

        return null; // ou retourner un chemin d'image par défaut
    }

    public function getEventsAssoRecommender($organizationSlug, string $formTypes): int
    {
        $url = "https://api.helloasso.com/v5/organizations/{$organizationSlug}/forms?formTypes={$formTypes}&states=Public";
       
        $data_forms = $this->helloAssoApiService->makeApiCall($url);
        if ($formTypes === 'Event') {
            $filteredData = $this->dataFilterAndPaginator->filterAndSortData($data_forms['data']);
        } elseif ($formTypes === 'Membership' || $formTypes === 'CrowdFunding') {
            $filteredData = $this->dataFilterAndPaginator->filterMemberShipSortData($data_forms['data']);
        } else {
            $filteredData = $data_forms['data'];
        }
        
        $count = count($filteredData);

        return $count;
    }

    public function urlDecode($value)
    {
        return urldecode($value);
    }
}
