<?php

namespace App\Form;

use App\Entity\Tih;
use App\Entity\Competence;
use App\Repository\CompetenceRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Constraints\File;

/**
 * @extends AbstractType<Tih>
 */

class TihType extends AbstractType
{
    private const MAX_FILE_SIZE = '5M';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('civilite', ChoiceType::class, [
                'choices' => [
                    'Monsieur' => 'Mr',
                    'Madame' => 'Mme',
                    'Autre' => 'Autre',
                ],
                'required' => false,
                'label' => 'Civilité',
                'placeholder' => 'Sélectionnez une civilité',
                'property_path' => 'title',
                'attr' => ['class' => 'form-select'],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'property_path' => 'lastName',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'family-name',
                    'maxlength' => 80,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre nom.']),
                    new Assert\Length([
                        'max' => 80,
                        'maxMessage' => 'Le nom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'property_path' => 'firstName',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'given-name',
                    'maxlength' => 80,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre prénom.']),
                    new Assert\Length([
                        'max' => 80,
                        'maxMessage' => 'Le prénom ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('emailPro', EmailType::class, [
                'label' => 'Email professionnel',
                'property_path' => 'professionalEmail',
                'help' => 'Cette adresse sera utilisée pour vous contacter depuis l’annuaire TIH.',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'email',
                    'maxlength' => 180,
                    'placeholder' => 'prenom.nom@exemple.fr',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre email professionnel.']),
                    new Assert\Email(['message' => 'Veuillez renseigner un email valide.']),
                    new Assert\Length([
                        'max' => 180,
                        'maxMessage' => 'L’email ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Téléphone',
                'property_path' => 'phone',
                'help' => 'Exemple : +33 6 12 34 56 78.',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'tel',
                    'maxlength' => 30,
                    'placeholder' => '+33 6 12 34 56 78',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre téléphone.']),
                    new Assert\Length([
                        'max' => 30,
                        'maxMessage' => 'Le téléphone ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                    new Assert\Regex([
                        'pattern' => '/^\+?[0-9 .()\/-]{6,30}$/',
                        'message' => 'Le numéro de téléphone n’a pas un format valide.',
                    ]),
                ],
            ])
            ->add('adresse', TextType::class, [
                'required' => false,
                'label' => 'Adresse',
                'property_path' => 'address',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'street-address',
                    'maxlength' => 180,
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 180,
                        'maxMessage' => 'L’adresse ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('codePostal', TextType::class, [
                'label' => 'Code postal',
                'property_path' => 'postalCode',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'postal-code',
                    'inputmode' => 'numeric',
                    'maxlength' => 10,
                    'placeholder' => '06000',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre code postal.']),
                    new Assert\Regex([
                        'pattern' => '/^\d{5}$/',
                        'message' => 'Veuillez renseigner un code postal français à 5 chiffres.',
                    ]),
                ],
            ])
            ->add('ville', TextType::class, [
                'label' => 'Ville',
                'property_path' => 'city',
                'attr' => [
                    'class' => 'form-control',
                    'autocomplete' => 'address-level2',
                    'maxlength' => 120,
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre ville.']),
                    new Assert\Length([
                        'max' => 120,
                        'maxMessage' => 'La ville ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('disponibilite', TextareaType::class, [
                'required' => false,
                'label' => 'Disponibilités',
                'property_path' => 'availability',
                'help' => 'Indiquez vos créneaux habituels, zones de déplacement ou contraintes utiles.',
                'attr' => [
                    'class' => 'form-control',
                    'rows' => 4,
                    'maxlength' => 1000,
                    'placeholder' => 'Ex. disponible les lundis et jeudis, missions à distance possibles...',
                ],
                'constraints' => [
                    new Assert\Length([
                        'max' => 1000,
                        'maxMessage' => 'Les disponibilités ne peuvent pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('dateDisponibilite', DateType::class, [
                'required' => false,
                'label' => 'Date de disponibilité',
                'property_path' => 'availabilityDate',
                'widget' => 'single_text',
                'attr' => ['class' => 'form-control'],
            ])
            ->add('tarif', NumberType::class, [
                'required' => false,
                'label' => 'Tarif',
                'property_path' => 'rate',
                'scale' => 2,
                'html5' => true,
                'attr' => [
                    'class' => 'form-control',
                    'min' => 0,
                    'step' => '0.01',
                    'placeholder' => 'Ex. 350',
                ],
                'constraints' => [
                    new Assert\PositiveOrZero(['message' => 'Le tarif ne peut pas être négatif.']),
                    new Assert\LessThanOrEqual([
                        'value' => 999999.99,
                        'message' => 'Le tarif saisi est trop élevé.',
                    ]),
                ],
            ])
            ->add('typeTarif', ChoiceType::class, [
                'required' => false,
                'label' => 'Type de tarif',
                'property_path' => 'rateType',
                'placeholder' => 'Sélectionnez un type de tarif',
                'choices' => [
                    'Par heure' => 'heure',
                    'Par jour' => 'jour',
                    'Par mission' => 'mission',
                ],
                'attr' => ['class' => 'form-select'],
            ])
            ->add('region', TextType::class, [
                'label' => 'Région',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 100,
                    'placeholder' => 'Provence-Alpes-Côte d’Azur',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre région.']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'La région ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('departement', TextType::class, [
                'label' => 'Département',
                'attr' => [
                    'class' => 'form-control',
                    'maxlength' => 100,
                    'placeholder' => 'Alpes-Maritimes',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre département.']),
                    new Assert\Length([
                        'max' => 100,
                        'maxMessage' => 'Le département ne peut pas dépasser {{ limit }} caractères.',
                    ]),
                ],
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (JPG, PNG, WebP)',
                'mapped' => false,
                'required' => false,
                'help' => 'Image carrée recommandée. Taille maximale : 5 Mo.',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'image/jpeg,image/png,image/webp',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => self::MAX_FILE_SIZE,
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image JPEG, PNG ou WebP.',
                        'maxSizeMessage' => 'La photo ne peut pas dépasser {{ limit }} {{ suffix }}.',
                    ])
                ],
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV (PDF uniquement)',
                'mapped' => false,
                'required' => false,
                'help' => 'Taille maximale : 5 Mo.',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'application/pdf',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => self::MAX_FILE_SIZE,
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
                        'maxSizeMessage' => 'Le CV ne peut pas dépasser {{ limit }} {{ suffix }}.',
                    ])
                ],
            ])
            ->add('competences', EntityType::class, [
                'class' => Competence::class,
                'choice_label' => 'name',
                'query_builder' => fn (CompetenceRepository $repository) => $repository->createQueryBuilder('c')->orderBy('c.name', 'ASC'),
                'multiple' => true,
                'expanded' => false,
                'by_reference' => false,
                'required' => false,
                'label' => 'Compétences',
                'help' => 'Maintenez Ctrl ou Cmd pour sélectionner plusieurs compétences.',
                'attr' => [
                    'class' => 'form-select',
                    'size' => 8,
                ],
            ])
            ->add('siret', TextType::class, [
                'label' => 'Numéro de SIRET',
                'help' => '14 chiffres, sans espace.',
                'attr' => [
                    'class' => 'form-control',
                    'inputmode' => 'numeric',
                    'maxlength' => 14,
                    'placeholder' => '12345678900012',
                ],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre numéro de SIRET.']),
                    new Assert\Regex([
                        'pattern' => '/^\d{14}$/',
                        'message' => 'Le SIRET doit contenir exactement 14 chiffres.',
                    ]),
                ],
            ])
            ->add('attestationTih', FileType::class, [
                'label' => 'Attestation TIH (PDF uniquement)',
                'mapped' => false,
                'required' => false,
                'help' => 'Document PDF demandé pour la validation administrative. Taille maximale : 5 Mo.',
                'attr' => [
                    'class' => 'form-control',
                    'accept' => 'application/pdf',
                ],
                'constraints' => [
                    new File([
                        'maxSize' => self::MAX_FILE_SIZE,
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
                        'maxSizeMessage' => 'L’attestation ne peut pas dépasser {{ limit }} {{ suffix }}.',
                    ])
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tih::class,
        ]);
    }
}
