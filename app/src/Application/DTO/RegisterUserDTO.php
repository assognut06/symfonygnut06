<?php

namespace App\Application\DTO;

use Symfony\Component\Validator\Constraints as Assert;

class RegisterUserDTO
{
    #[Assert\NotBlank(message: 'Veuillez entrer votre email')]
    #[Assert\Email(message: 'Veuillez entrer un email valide')]
    public ?string $email = null;

    #[Assert\NotBlank(message: 'Veuillez entrer un mot de passe')]
    #[Assert\Length(
        min: 8,
        minMessage: 'Votre mot de passe doit comporter au moins {{ limit }} caractères',
        max: 4096
    )]
    public ?string $plainPassword = null;

    #[Assert\NotBlank(message: 'Veuillez confirmer votre mot de passe')]
    #[Assert\EqualTo(
        propertyPath: 'plainPassword',
        message: 'Les mots de passe doivent correspondre.'
    )]
    public ?string $confirmPassword = null;

    #[Assert\IsTrue(message: 'Vous devez accepter nos conditions.')]
    public bool $agreeTerms = false;

    public bool $isTih = false;
}
