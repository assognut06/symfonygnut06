<?php

namespace App\Form;

use App\Entity\Tih;
use App\Entity\User;
use App\Entity\Competence;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, EmailType, TelType, TextareaType, DateTimeType, ChoiceType, DateType, NumberType

};
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class TihType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('civilite', ChoiceType::class, [
                'choices' => [
                    'Monsieur' => 'Mr',
                    'Madame' => 'Mme',
                    'Autre' => 'Autre'
                ],
                'required' => false,
                'label' => 'Civilité',
                'property_path' => 'title'
            ])
            ->add('nom', TextType::class, [
                'required' => false,
                'property_path' => 'lastName'
            ])
            ->add('prenom', TextType::class, [
                'required' => false,
                'property_path' => 'firstName'
            ])
            ->add('emailPro', EmailType::class, [
                'required' => false,
                'property_path' => 'professionalEmail'
            ])
            ->add('telephone', TelType::class, [
                'required' => false,
                'property_path' => 'phone'
            ])
            ->add('adresse', TextType::class, [
                'required' => false,
                'property_path' => 'address'
            ])
            ->add('codePostal', TextType::class, [
                'required' => false,
                'property_path' => 'postalCode'
            ])
            ->add('ville', TextType::class, [
                'required' => false,
                'property_path' => 'city'
            ])
            ->add('disponibilite', TextareaType::class, [
                'required' => false,
                'property_path' => 'availability'
            ])
            ->add('dateDisponibilite', DateType::class, [
                'required' => false,
                'label' => 'Date de disponibilité',
                'property_path' => 'availabilityDate',
                'widget' => 'single_text'
            ])
            ->add('tarif', NumberType::class, [
                'required' => false,
                'label' => 'Tarif',
                'property_path' => 'rate',
                'attr' => ['step' => '0.01']
            ])
            ->add('typeTarif', ChoiceType::class, [
                'required' => false,
                'label' => 'Type de tarif',
                'property_path' => 'rateType',
                'choices' => [
                    'Par heure' => 'heure',
                    'Par jour' => 'jour',
                    'Par mission' => 'mission'
                ]
            ])
            ->add('region', TextType::class, [
                'required' => false,
                'label' => 'Région'
            ])
            ->add('departement', TextType::class, [
                'required' => false,
                'label' => 'Département'
            ])
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil (JPG, PNG)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['image/jpeg', 'image/png'],
                        'mimeTypesMessage' => 'Veuillez télécharger une image JPEG ou PNG.',
                    ])
                ],
            ])
            ->add('cv', FileType::class, [
                'label' => 'CV (PDF uniquement)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
                    ])
                ],
            ])
            ->add('competences', EntityType::class, [
                'class' => Competence::class,
                'choice_label' => 'name',
                'multiple' => true,
                'expanded' => false, // ✅ select multiple compact
                'required' => false,
                'label' => 'Compétences',
            ])
            ->add('siret', TextType::class, [
                'required' => false,
                'label' => 'Numéro de SIRET'
            ])
            ->add('attestationTih', FileType::class, [
                'label' => 'Attestation TIH (PDF uniquement)',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '5M',
                        'mimeTypes' => ['application/pdf'],
                        'mimeTypesMessage' => 'Veuillez télécharger un fichier PDF valide.',
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
