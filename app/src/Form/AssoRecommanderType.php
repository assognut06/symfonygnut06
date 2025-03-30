<?php

namespace App\Form;

use App\Entity\AssoRecommander;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class AssoRecommanderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organizationSlug', TextType::class, [
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Le slug de l\'organisation ne peut pas dépasser 255 caractères.',
                    ]),
                ],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssoRecommander::class,
        ]);
    }
}
