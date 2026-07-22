<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
/**
 * @extends AbstractType<array<mixed>>
 */
class ContactType extends AbstractType
{
    public const PROJECT_TYPES = [
        'Site web simple' => 'site_web',
        'Application mobile' => 'application',
        'Salle 3D Immersive' => 'salle_3d',
        'Grands projets' => 'grand_projet',
        'Autre question' => 'autre_question',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('first_name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre prénom.']),
                    new Assert\Length([
                        'max' => 80,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('last_name', TextType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre nom.']),
                    new Assert\Length([
                        'max' => 80,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre email.']),
                    new Assert\Email(['message' => 'Cette adresse email n\'est pas valide.']),
                    new Assert\Length([
                        'max' => 180,
                        'maxMessage' => 'L\'email ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('tel', TextType::class, [
                'required' => false,
                'constraints' => [
                    new Assert\Length([
                        'max' => 30,
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^$|^\+?[0-9 .()\/-]{6,30}$/',
                        'message' => 'Le numéro de téléphone n\'a pas un format valide.',
                    ]),
                ],
            ])
            ->add('project_type', ChoiceType::class, [
                'choices' => self::PROJECT_TYPES,
                'invalid_message' => 'Veuillez sélectionner un type de projet valide.',
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez sélectionner un type de projet.']),
                    new Assert\Choice([
                        'choices' => array_values(self::PROJECT_TYPES),
                        'message' => 'Veuillez sélectionner un type de projet valide.',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre message.']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => 3000,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'allow_extra_fields' => true,
            'data_class' => null,
        ]);
    }

    public function getBlockPrefix(): string
    {
        return '';
    }
}
