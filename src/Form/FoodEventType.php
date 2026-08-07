<?php

namespace App\Form;

use App\Entity\FoodEvent;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use App\Enum\MealType;

class FoodEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('mealType', EnumType::class, [
                'class' => MealType::class,
                'label' => 'Type de repas',
            ])
            ->add('eatenAt', null, [
                'label' => 'Date et heure',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Repas',
            ])
            ->add('hungerLevel', IntegerType::class, [
                'label' => 'Faim (1-10)',
                'required' => false,
            ])
            ->add('pleasureLevel', IntegerType::class, [
                'label' => 'Plaisir (1-10)',
                'required' => false,
            ])
            ->add('cause', null, [
                'label' => 'Cause',
                'required' => false,
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Note',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodEvent::class,
        ]);
    }
}