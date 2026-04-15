<?php

namespace App\Form;

use App\Entity\Payers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre prénom',
                    'autocomplete' => 'given-name',
                    'aria-describedby' => 'firstName-error',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre prénom.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre nom',
                    'autocomplete' => 'family-name',
                    'aria-describedby' => 'lastName-error',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre nom.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre adresse',
                    'autocomplete' => 'street-address',
                    'aria-describedby' => 'address-error',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre adresse.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'L\'adresse ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('city', TextType::class, [
                'label' => 'Ville',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre ville',
                    'autocomplete' => 'address-level2',
                    'aria-describedby' => 'city-error',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre ville.',
                    ]),
                    new Assert\Length([
                        'max' => 255,
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('zipCode', TextType::class, [
                'label' => 'Code postal',
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre code postal',
                    'autocomplete' => 'postal-code',
                    'pattern' => '[0-9]{5}',
                    'aria-describedby' => 'zipCode-help zipCode-error',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'help' => 'Format : 5 chiffres (ex: 06000)',
                'help_attr' => [
                    'class' => 'form-text',
                    'id' => 'zipCode-help'
                ],
                'constraints' => [
                    new Assert\NotBlank([
                        'message' => 'Veuillez saisir votre code postal.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^[0-9]{5}$/',
                        'message' => 'Veuillez saisir un code postal valide (5 chiffres).',
                    ]),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'attr' => [
                    'class' => 'form-control',
                    'placeholder' => 'Entrez votre numéro de téléphone',
                    'autocomplete' => 'tel',
                    'pattern' => '[0-9+\-\s\(\)]+',
                    'aria-describedby' => 'phone-help phone-error',
                ],
                'label_attr' => [
                    'class' => 'form-label'
                ],
                'help' => 'Format : 06 12 34 56 78 ou +33 6 12 34 56 78',
                'help_attr' => [
                    'class' => 'form-text',
                    'id' => 'phone-help'
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 20,
                        'maxMessage' => 'Le numéro de téléphone ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Payers::class,
            'attr' => [
                'class' => 'needs-validation',
                'novalidate' => 'novalidate',
                'aria-describedby' => 'form-help',
            ],
        ]);
    }
}
