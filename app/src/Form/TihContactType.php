<?php

namespace App\Form;

use App\Application\DTO\Tih\TihContactDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<TihContactDTO>
 */

class TihContactType extends AbstractType
{
    private const NAME_MAX_LENGTH = 80;
    private const EMAIL_MAX_LENGTH = 180;
    private const PHONE_MAX_LENGTH = 30;
    private const SUBJECT_MAX_LENGTH = 150;
    private const MESSAGE_MAX_LENGTH = 3000;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Votre nom',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'family-name',
                    'maxlength' => self::NAME_MAX_LENGTH,
                    'placeholder' => 'Votre nom',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre nom.']),
                    new Assert\Length([
                        'max' => self::NAME_MAX_LENGTH,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Votre prénom',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'given-name',
                    'maxlength' => self::NAME_MAX_LENGTH,
                    'placeholder' => 'Votre prénom',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre prénom.']),
                    new Assert\Length([
                        'max' => self::NAME_MAX_LENGTH,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'required' => false,
                'empty_data' => null,
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'organization',
                    'maxlength' => 120,
                    'placeholder' => 'Entreprise ou organisation',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 120,
                        'maxMessage' => 'Le nom de l’entreprise ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'help' => 'Exemple : +33 6 12 34 56 78.',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'tel',
                    'maxlength' => self::PHONE_MAX_LENGTH,
                    'placeholder' => '+33 6 12 34 56 78',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre téléphone.']),
                    new Assert\Length([
                        'max' => self::PHONE_MAX_LENGTH,
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\+?[0-9 .()\/-]{6,30}$/',
                        'message' => 'Le numéro de téléphone n’a pas un format valide.',
                    ]),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'email',
                    'maxlength' => self::EMAIL_MAX_LENGTH,
                    'placeholder' => 'votre@email.fr',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre email.']),
                    new Assert\Email(['message' => 'Veuillez renseigner un email valide.']),
                    new Assert\Length([
                        'max' => self::EMAIL_MAX_LENGTH,
                        'maxMessage' => 'L’email ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => self::SUBJECT_MAX_LENGTH,
                    'placeholder' => 'Objet de votre demande',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner un objet.']),
                    new Assert\Length([
                        'max' => self::SUBJECT_MAX_LENGTH,
                        'maxMessage' => 'L’objet ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'help' => 'Minimum 10 caractères. Évitez d’indiquer des informations sensibles.',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 7,
                    'maxlength' => self::MESSAGE_MAX_LENGTH,
                    'placeholder' => 'Présentez votre besoin, votre contexte et vos disponibilités pour être recontacté.',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner un message.']),
                    new Assert\Length([
                        'min' => 10,
                        'max' => self::MESSAGE_MAX_LENGTH,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères.',
                        'maxMessage' => 'Le message ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TihContactDTO::class,
            'csrf_token_id' => 'tih_contact',
        ]);
    }
}
