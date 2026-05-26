<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;

class ProfilePictureType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('photo', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false, // ne lie pas directement ce champ à une propriété de l'entité
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '2M',
                        'extensions' => ['jpg', 'jpeg', 'png', 'webp', 'gif'],
                        'mimeTypes' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
                        'maxSizeMessage' => 'La taille de la photo ne doit pas dépasser 2 Mo.',
                        'extensionsMessage' => 'Veuillez télécharger une image valide (JPG, PNG, WEBP ou GIF).',
                    ]),
                ],
            ]);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
