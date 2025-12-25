<?php

namespace App\Form;

use App\Application\DTO\RegisterUserDTO;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('email', EmailType::class, [
                'attr' => ['autocomplete' => 'email'],
            ])
            ->add('plainPassword', PasswordType::class, [
                'attr' => ['autocomplete' => 'new-password'],
                'label' => 'Mot de passe',
            ])
            ->add('confirmPassword', PasswordType::class, [
                'attr' => ['autocomplete' => 'new-password'],
                'label' => 'Répétez le mot de passe',
            ])
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'Je suis en accord avec les valeurs de Gnut 06',
            ])
            ->add('isTih', CheckboxType::class, [
                'label' => 'Je suis une personne en situation de handicap (TIH)',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RegisterUserDTO::class,
        ]);
    }
}
