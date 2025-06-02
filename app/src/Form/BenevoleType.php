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
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Form\EventListener\PremiereLettreMajuscule;
use Symfony\Component\Validator\Constraints as Assert;

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
            'attr' => ['placeholder' => 'Email*', 'class' => 'form-control'],
            'constraints' => [
                new Assert\Email([
                    'message' => 'L\'adresse email n\'est pas valide.',
                ]),
                new Assert\NotBlank([
                    'message' => 'L\'email ne peut pas être vide.',
                ])
            ],
        ])
        ->add('email_pro', EmailType::class, [
            'required' => false,
            'attr' => ['placeholder' => 'Email professionnel', 'class' => 'form-control'],
            'constraints' => [
                new Assert\Email([
                    'message' => 'L\'adresse email n\'est pas valide.',
                ]),
            ],
        ])
        ->add('telephone', TextType::class, [
            'attr' => ['placeholder' => 'Téléphone*', 'class' => 'form-control'],
            'constraints' => [
                new Assert\NotBlank([
                    'message' => 'Le numéro de téléphone ne peut pas être vide.',
                ]),
                new Assert\Regex([
                    'pattern' => '/^(\+33|0)[1-9](?:[\s\.]?\d{2}){4}$/',
                    'message' => 'Veuillez entrer un numéro de téléphone valide.',
                ])
            ],
        ])
        ->add('adresse_1', TextType::class, [
            'required' => false,
            'attr' => ['placeholder' => 'Adresse ligne 1', 'class' => 'form-control']
        ])
        ->add('adresse_2', TextType::class, [
            'required' => false,
            'attr' => ['placeholder' => 'Adresse ligne 2 (optionnelle)', 'class' => 'form-control']
        ])
        ->add('code_postal', TextType::class, [
            'required' => false,
            'attr' => ['placeholder' => 'Code postal', 'class' => 'form-control']
        ])
        ->add('ville', TextType::class, [
            'required' => false,
            'attr' => ['placeholder' => 'Ville', 'class' => 'form-control']
        ])
        ->add('pays', TextType::class, [
            'required' => false,
            'attr' => ['placeholder' => 'Pays', 'class' => 'form-control']
        ])
        ->add('asso_trouve_par', ChoiceType::class, [
            'required' => false,
            'choices' => [
                'Site web' => 'Site web',
                'Réseaux sociaux' => 'Réseaux sociaux',
                'Autre' => 'Autre',
            ],
            'placeholder' => 'Comment vous nous avez trouver ?',
            'attr' => ['class' => 'form-select']
        ])

        ->add('commentaire', TextareaType::class, [
            'required' => false,
            'attr' => [
                'placeholder' => 'Votre commentaire ici...',
                'class' => 'form-control',
                'rows' => 5, // nombre de lignes visibles
            ],
            'label' => 'Commentaire',
        ])
        
        ->add('cv', FileType::class, [
            'label' => 'Votre CV (PDF uniquement)',
            'mapped' => false,
            'required' => false,
            'constraints' => [
                new File([
                    'maxSize' => '5M',
                    'mimeTypes' => [
                        'application/pdf',
                    ],
                    'mimeTypesMessage' => 'Veuillez uploader un fichier PDF valide.',
                ])
            ],
            'attr' => ['class' => 'form-control', 'accept' => '.pdf']
        ]);
        
        $builder->addEventSubscriber(new PremiereLettreMajuscule());

}

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Benevole::class,
        ]);
    }
}
