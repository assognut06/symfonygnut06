<?php

namespace App\Form;

use App\Entity\AssoRecommander;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Formulaire complet utilisé par le CRUD admin (AssosCrudController).
 * Remplace l'ancien AssoRecommander1Type.
 *
 * À ne pas confondre avec AssoRecommanderType, qui ne contient que le slug
 * et sert à l'import HelloAsso (AdminAssoRecommanderController::new).
 *
 * createdAt / updatedAt ne sont pas exposés : ils sont gérés par l'entité
 * (constructeur + #[ORM\PreUpdate]).
 *
 * @extends AbstractType<AssoRecommander>
 */
class AssoRecommanderCrudType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('organizationSlug')
            ->add('banner')
            ->add('fiscalReceiptEligibility')
            ->add('fiscalReceiptIssuanceEnabled')
            ->add('type')
            ->add('category')
            ->add('logo')
            ->add('name')
            ->add('city')
            ->add('zipCode')
            ->add('description')
            ->add('url')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => AssoRecommander::class,
        ]);
    }
}
