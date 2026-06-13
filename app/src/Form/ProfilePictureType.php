<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

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
                    new Assert\File([
                        'maxSize' => '10M',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/webp',
                            'image/gif',
                        ],
                        'maxSizeMessage' => 'Votre photo est trop lourde ({{ size }} {{ suffix }}). La taille maximale est {{ limit }} {{ suffix }}.',
                        'mimeTypesMessage' => 'Veuillez choisir une image JPG, PNG, WebP ou GIF.',
                        'uploadIniSizeErrorMessage' => 'Votre photo depasse la taille autorisee par le serveur.',
                    ]),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([]);
    }
}
