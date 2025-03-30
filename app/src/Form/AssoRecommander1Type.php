<?php

namespace App\Form;

use App\Entity\AssoRecommander;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class AssoRecommander1Type extends AbstractType
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
            ->add('CreatedAt', null, [
                'widget' => 'single_text',
            ])
            ->add('UpdatedAt', null, [
                'widget' => 'single_text',
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
