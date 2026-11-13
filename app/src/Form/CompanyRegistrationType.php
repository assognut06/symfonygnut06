<?php

namespace App\Form;

use App\Entity\CompteEntreprise;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class CompanyRegistrationType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options
    ): void {
        $builder
            ->add('raisonSociale', TextType::class, [
                'label' => 'Raison sociale',
                'required' => true,
                'attr' => [
                    'maxlength' => 160,
                    'placeholder' => 'Nom officiel de votre entreprise',
                    'autocomplete' => 'organization',
                ],
            ])
            ->add('identifiantLegal', TextType::class, [
                'label' => 'SIREN ou SIRET',
                'required' => false,
                'help' => 'Renseignez votre SIREN (9 chiffres) ou SIRET (14 chiffres), si vous en possédez un.',
                'attr' => [
                    'maxlength' => 14,
                    'placeholder' => 'Ex. 123456789',
                ],
            ])
            ->add('nomContact', TextType::class, [
                'label' => 'Nom du contact principal',
                'required' => true,
                'attr' => [
                    'maxlength' => 150,
                    'placeholder' => 'Nom et prénom du responsable',
                    'autocomplete' => 'name',
                ],
            ])
            ->add('emailContact', EmailType::class, [
                'label' => 'Adresse e-mail professionnelle',
                'required' => true,
                'attr' => [
                    'maxlength' => 180,
                    'placeholder' => 'contact@entreprise.fr',
                    'autocomplete' => 'email',
                ],
            ])
            ->add('telephone', TelType::class, [
                'label' => 'Numéro de téléphone',
                'required' => false,
                'attr' => [
                    'maxlength' => 20,
                    'placeholder' => 'Ex. 06 12 34 56 78',
                    'autocomplete' => 'tel',
                ],
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Envoyer ma demande',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(
        OptionsResolver $resolver
    ): void {
        $resolver->setDefaults([
            'data_class' => CompteEntreprise::class,
            'csrf_protection' => true,
            'csrf_token_id' => 'company_registration',
        ]);
    }
}