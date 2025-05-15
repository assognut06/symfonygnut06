<?php

namespace App\Form;

use App\Entity\PersonnePhysique;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Form\EventListener\PremiereLettreMajuscule;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;


class PersonnePhysiqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('civilite', ChoiceType::class, [
                'choices' => [
                    'M.' => 'M.',
                    'Mme' => 'Mme',
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
             ->add('pays', HiddenType::class, [
                        'data' => 'France',
                        'mapped' => true, 
            ]);

            $builder->addEventSubscriber(new PremiereLettreMajuscule());
    }
    

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PersonnePhysique::class,
        ]);
    }
}
