<?php

namespace App\Form;

use App\Entity\Casque;
use App\Entity\Marque;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class CasqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('marque', EntityType::class, [
                'class' => Marque::class,
                'choice_label' => 'nom',
                'label' => false,
                'attr' => ['placeholder' => 'Modèle',
                           'class' => 'form-select'],
            ])
            ->add('etat', ChoiceType::class, [
                'choices' => [
                    'Neuf' => 'Neuf',
                    'Occasion (Bon état)' => 'Occasion (Bon état)',
                    'Occasion (Moyen état)' => 'Occasion (Moyen état)',
                    'Cassé' => 'Cassé'
                ],
                'label' => false,
                'attr' => ['placeholder' => 'Etat',
                               'class' => 'form-select mt-3'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Casque::class,
        ]);
    }
}