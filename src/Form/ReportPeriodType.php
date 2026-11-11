<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;

class ReportPeriodType extends AbstractType
{
    public function buildForm(
        FormBuilderInterface $builder,
        array $options,
    ): void {
        $builder
            ->add('startDate', DateType::class, [
                'label' => 'Date de début',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('endDate', DateType::class, [
                'label' => 'Date de fin des indicateurs alimentaires',
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('habitSummary', TextareaType::class, [
                'label' => 'Évolution des habitudes (facultatif)',
                'required' => false,
                'help' => 'Décris uniquement les changements que tu souhaites communiquer au médecin.',
                'attr' => [
                    'maxlength' => 350,
                    'rows' => 4,
                    'placeholder' => 'Ex. Diminution des boissons sucrées, portions adaptées, marche plus régulière…',
                ],
                'constraints' => [new Length(max: 350, maxMessage: 'La synthèse est limitée à {{ limit }} caractères.')],
            ])
            ->add('medicalNotes', TextareaType::class, [
                'label' => 'Informations complémentaires (facultatif)',
                'required' => false,
                'help' => 'Ressenti, symptômes, limitations ou questions à aborder en consultation.',
                'attr' => [
                    'maxlength' => 350,
                    'rows' => 4,
                    'placeholder' => 'Ex. Fatigue récente, gêne pendant l’activité, question à poser au médecin…',
                ],
                'constraints' => [new Length(max: 350, maxMessage: 'Les informations sont limitées à {{ limit }} caractères.')],
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
        ]);
    }
}
