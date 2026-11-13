<?php


namespace App\Form;

use App\Entity\Competence;
use App\Entity\Mission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MissionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre', TextType::class, [
                'label' => 'Titre de la mission',
                'required' => false,
                'attr' => ['maxlength' => 120],
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description de la mission',
                'required' => false,
                'attr' => ['maxlength' => 3000, 'rows' => 8],
            ])
            ->add('competences', EntityType::class, [
                'label' => 'Compétences recherchées',
                'class' => Competence::class,
                'choice_label' => static function (Competence $competence): string {
                    foreach (['getLibelle', 'getNom', 'getName'] as $getter) {
                        if (method_exists($competence, $getter)) {
                            $label = $competence->{$getter}();
                            if (is_string($label) && $label !== '') {
                                return $label;
                            }
                        }
                    }

                    return 'Compétence ' . (string) $competence->getId();
                },
                'multiple' => true,
                'expanded' => false,
                'required' => false,
                'by_reference' => false,
                'attr' => ['size' => 8],
            ])
            ->add('domaine', TextType::class, [
                'label' => 'Domaine professionnel',
                'required' => false,
                'attr' => ['maxlength' => 120],
            ])
            ->add('zone', TextType::class, [
                'label' => 'Région ou département',
                'required' => false,
                'attr' => ['maxlength' => 120],
            ])
            ->add('modalite', ChoiceType::class, [
                'label' => 'Modalité de réalisation',
                'placeholder' => 'Choisir une modalité',
                'choices' => [
                    'À distance' => Mission::MODALITY_REMOTE,
                    'Sur place' => Mission::MODALITY_ONSITE,
                    'Hybride' => Mission::MODALITY_HYBRID,
                ],
                'required' => false,
            ])
            ->add('dateDebutSouhaitee', DateType::class, [
                'label' => 'Date de début souhaitée',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('dureeEstimee', TextType::class, [
                'label' => 'Durée estimée',
                'required' => false,
                'attr' => ['maxlength' => 100],
            ])
            ->add('dateLimiteReponse', DateType::class, [
                'label' => 'Date limite de réponse',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
                'required' => false,
            ])
            ->add('budgetMin', NumberType::class, [
                'label' => 'Tarif minimum (€)',
                'required' => false,
                'scale' => 2,
                'input' => 'string',
                'html5' => true,
                'attr' => ['min' => '0.01', 'step' => '0.01'],
            ])
            ->add('budgetMax', NumberType::class, [
                'label' => 'Tarif maximum (€)',
                'required' => false,
                'scale' => 2,
                'input' => 'string',
                'html5' => true,
                'attr' => ['min' => '0.01', 'step' => '0.01'],
            ])
            ->add('uniteBudget', ChoiceType::class, [
                'label' => 'Unité du tarif',
                'placeholder' => 'À discuter / non renseigné',
                'choices' => [
                    'Par heure' => Mission::BUDGET_HOUR,
                    'Par jour' => Mission::BUDGET_DAY,
                    'Forfait' => Mission::BUDGET_FIXED,
                ],
                'required' => false,
            ])
            ->add('saveDraft', SubmitType::class, [
                'label' => 'Enregistrer le brouillon',
                'attr' => ['class' => 'btn btn-outline-primary'],
            ])
            ->add('submitForReview', SubmitType::class, [
                'label' => 'Soumettre à validation',
                'attr' => ['class' => 'btn btn-primary'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Mission::class,
            'validation_groups' => static function (FormInterface $form): array {
                return $form->get('submitForReview')->isClicked()
                    ? ['Default', 'mission_submit']
                    : ['Default'];
            },
        ]);
    }
}
