<?php

namespace App\Form;

use App\Entity\Benevole;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;

class BenevoleType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
        ->add('type', ChoiceType::class, [
            'choices' => [
                'Bénévole' => 'Bénévole',
                'Stagiaire' => 'Stagiaire',
            ],
            'placeholder' => 'Type de bénévole',
            'attr' => ['class' => 'form-select']
        ])
        ->add('civilite', ChoiceType::class, [
            'choices' => [
                'Monsieur' => 'M.',
                'Madame' => 'Mme',
            ],
            'placeholder' => 'Civilité*',
            'attr' => ['class' => 'form-select']
        ])
        ->add('nom', TextType::class, [
            'attr' => ['placeholder' => 'Nom*', 'class' => 'form-control']
        ])
        ->add('prenom', TextType::class, [
            'attr' => ['placeholder' => 'Prénom*', 'class' => 'form-control']
        ])
        ->add('email', EmailType::class, [
            'attr' => ['placeholder' => 'Email*', 'class' => 'form-control']
        ])
        ->add('telephone', TextType::class, [
            'attr' => ['placeholder' => 'Téléphone*', 'class' => 'form-control']
        ])
        ->add('adresse_1', TextType::class, [
            'attr' => ['placeholder' => 'Adresse ligne 1*', 'class' => 'form-control']
        ])
        ->add('adresse_2', TextType::class, [
            'required' => false,
            'attr' => ['placeholder' => 'Adresse ligne 2 (optionnelle)', 'class' => 'form-control']
        ])
        ->add('code_postal', TextType::class, [
            'attr' => ['placeholder' => 'Code postal*', 'class' => 'form-control']
        ])
        ->add('ville', TextType::class, [
            'attr' => ['placeholder' => 'Ville*', 'class' => 'form-control']
        ])
        ->add('pays', TextType::class, [
            'attr' => ['placeholder' => 'Pays*', 'class' => 'form-control']
        ])
        ->add('asso_trouve_par', ChoiceType::class, [
            'choices' => [
                'Site web' => 'Site web',
                'Réseaux sociaux' => 'Réseaux sociaux',
                'Autre' => 'Autre',
            ],
            'placeholder' => 'Comment vous nous avez trouver ?*',
            'attr' => ['class' => 'form-select']
        ])

        ->add('cv', FileType::class, [
            'label' => 'Votre CV (PDF ou DOC)',
            'mapped' => false, // très important car ce champ ne correspond pas à une propriété directement modifiable de l'entité
            'required' => false,
            'constraints' => [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'application/pdf',
                        'application/msword',
                        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    ],
                    'mimeTypesMessage' => 'Veuillez uploader un fichier PDF ou Word valide.',
                ])
            ],
            'attr' => ['class' => 'form-control']
        ]);
        
}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Benevole::class,
        ]);
    }
}
