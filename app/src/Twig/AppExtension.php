<?php
// src/Twig/AppExtension.php
namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFunctions()
    {
        return [
            new TwigFunction('formattedPrice', [$this, 'formatPrice']),
        ];
    }

    public function formatPrice($number)
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
}
