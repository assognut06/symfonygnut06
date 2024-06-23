<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use Symfony\Component\Finder\Finder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class AppExtension extends AbstractExtension
{
    private $parameterBag;

    public function __construct(ParameterBagInterface $parameterBag)
    {
        $this->parameterBag = $parameterBag;
    }
    public function getFunctions()
    {
        return [
            new TwigFunction('formattedPrice', [$this, 'formatPrice']),
            new TwigFunction('randomImage', [$this, 'randomImage']),
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
            return $directory.$randomFile->getRelativePathname();
        }
    
        return null; // ou retourner un chemin d'image par défaut
    }
}
