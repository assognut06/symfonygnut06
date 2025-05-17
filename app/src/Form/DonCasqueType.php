<?php

namespace App\Form;

use App\Entity\Don;
use App\Entity\Casque;
use App\Entity\ModeLivraison;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class DonCasqueType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nombre_casques', IntegerType::class, [
                'label' => 'Nombre de casques*',
                'attr' => ['placeholder' => 'Ex: 2'],
                'mapped' => false,
            ])
            ->add('casques', CollectionType::class, [
                'entry_type' => CasqueType::class,
                'entry_options' => ['label' => false],
                'allow_add' => true,
                'by_reference' => false,
                'label' => false,
            ])
            ->add('modeLivraison', EntityType::class, [
                'class' => ModeLivraison::class,
                'choice_label' => 'nom',
                'label' => 'Mode de livraison*',
            ])
            ->add('message', TextareaType::class, [
                'label' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Ajoutez un message si nécessaire'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Don::class,
        ]);
    }
}