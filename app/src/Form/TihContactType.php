<?php

namespace App\Form;

use App\Application\DTO\Tih\TihContactDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

class TihContactType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Votre nom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre nom']),
                ],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Votre prénom',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre prénom']),
                ],
            ])
            ->add('entreprise', TextType::class, [
                'label' => 'Nom de l\'entreprise',
                'required' => false,
                'attr' => ['class' => 'form-control'],
            ])
            ->add('telephone', TextType::class, [
                'label' => 'Téléphone',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre téléphone']),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Adresse email',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner votre email']),
                    new Assert\Email(['message' => 'Veuillez renseigner un email valide']),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Objet',
                'attr' => ['class' => 'form-control'],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner un objet']),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['class' => 'form-control', 'rows' => 6],
                'constraints' => [
                    new Assert\NotBlank(['message' => 'Veuillez renseigner un message']),
                    new Assert\Length([
                        'min' => 10,
                        'minMessage' => 'Le message doit contenir au moins {{ limit }} caractères',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TihContactDTO::class,
        ]);
    }
}
