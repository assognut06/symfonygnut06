<?php

namespace App\Form;

use App\Entity\Tih;
use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Form\Extension\Core\Type\{
    TextType, EmailType, TelType, TextareaType, DateTimeType, ChoiceType
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
                'label' => 'Civilité'
            ])
            ->add('nom', TextType::class, ['required' => false])
            ->add('prenom', TextType::class, ['required' => false])
            ->add('emailPro', EmailType::class, ['required' => false])
            ->add('telephone', TelType::class, ['required' => false])
            ->add('adresse', TextType::class, ['required' => false])
            ->add('codePostal', TextType::class, ['required' => false])
            ->add('ville', TextType::class, ['required' => false])
            ->add('disponibilite', TextareaType::class, ['required' => false])
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
            ->add('competences', TextareaType::class, ['required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Tih::class,
        ]);
    }
}
