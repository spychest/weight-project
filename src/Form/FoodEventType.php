<?php

namespace App\Form;

use App\Entity\FavoriteMeal;
use App\Entity\FoodEvent;
use App\Enum\MealType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class FoodEventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('favoriteMeal', ChoiceType::class, [
                'label' => 'Repas favori ou habituel',
                'mapped' => false,
                'required' => false,
                'placeholder' => 'Choisir un repas enregistré',
                'choices' => $options['favorite_meals'],
                'choice_label' => static fn (FavoriteMeal $favoriteMeal): string => (string) $favoriteMeal->getName(),
                'choice_value' => static fn (?FavoriteMeal $favoriteMeal): string => $favoriteMeal?->getId() === null ? '' : (string) $favoriteMeal->getId(),
                'choice_attr' => static fn (FavoriteMeal $favoriteMeal): array => [
                    'data-meal-description' => (string) $favoriteMeal->getDescription(),
                ],
            ])
            ->add('mealType', EnumType::class, [
                'class' => MealType::class,
                'label' => 'Type de repas',
                'choice_label' => static fn (MealType $mealType): string => $mealType->label(),
            ])
            ->add('eatenAt', null, [
                'label' => 'Date et heure',
                'widget' => 'single_text',
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
            ])
            ->add('saveAsFavorite', CheckboxType::class, [
                'label' => 'Enregistrer ce repas dans mes favoris',
                'mapped' => false,
                'required' => false,
            ])
            ->add('favoriteMealName', TextType::class, [
                'label' => 'Nom du favori',
                'mapped' => false,
                'required' => false,
                'attr' => ['placeholder' => 'Ex. Chili, Burger, Fromage blanc banane raisin'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => FoodEvent::class,
            'favorite_meals' => [],
        ]);
        $resolver->setAllowedTypes('favorite_meals', 'array');
    }
}
