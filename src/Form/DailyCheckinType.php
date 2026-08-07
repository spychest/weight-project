<?php

namespace App\Form;

use App\Entity\DailyCheckin;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DailyCheckinType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'label' => 'Date',
            ])
            ->add('moodLevel', IntegerType::class, [
                'label' => 'Humeur (1-10)',
            ])
            ->add('energyLevel', IntegerType::class, [
                'label' => 'Énergie (1-10)',
            ])
            ->add('frustrationLevel', IntegerType::class, [
                'label' => 'Frustration (1-10)',
            ])
            ->add('painLevel', IntegerType::class, [
                'label' => 'Douleur (1-10)',
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => DailyCheckin::class,
        ]);
    }
}