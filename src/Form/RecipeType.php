<?php

namespace App\Form;

use App\Entity\Recipe;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

final class RecipeType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $collectionOptions = ['allow_add' => true, 'allow_delete' => true, 'by_reference' => false, 'prototype' => true];

        $builder
            ->add('title', TextType::class, ['label' => 'Titre', 'attr' => ['placeholder' => 'Ex. Curry de légumes']])
            ->add('setupTimeMinutes', IntegerType::class, ['label' => 'Temps de mise en place (minutes)', 'required' => false, 'attr' => ['min' => 0]])
            ->add('preparationTimeMinutes', IntegerType::class, ['label' => 'Temps de préparation (minutes)', 'required' => false, 'attr' => ['min' => 0]])
            ->add('servings', IntegerType::class, ['label' => 'Nombre de portions', 'attr' => ['min' => 1]])
            ->add('photo', FileType::class, [
                'label' => 'Photo',
                'mapped' => false,
                'required' => false,
                'constraints' => [new Image(maxSize: '5M', mimeTypesMessage: 'Sélectionne une image valide.')],
                'attr' => ['accept' => 'image/*'],
            ])
            ->add('vegetarian', CheckboxType::class, [
                'label' => 'Végétarien',
                'required' => false,
            ])
            ->add('vegan', CheckboxType::class, [
                'label' => 'Végan',
                'required' => false,
            ])
            ->add('glutenFree', CheckboxType::class, [
                'label' => 'Sans gluten',
                'required' => false,
            ])
            ->add('utensils', CollectionType::class, $collectionOptions + [
                'entry_type' => TextType::class,
                'entry_options' => ['label' => false, 'attr' => ['placeholder' => 'Ex. Une grande casserole']],
            ])
            ->add('ingredients', CollectionType::class, $collectionOptions + ['entry_type' => RecipeIngredientType::class])
            ->add('setupSteps', CollectionType::class, $collectionOptions + [
                'entry_type' => TextareaType::class,
                'entry_options' => ['label' => false, 'attr' => ['rows' => 2, 'placeholder' => 'Ex. Émincer les légumes']],
            ])
            ->add('preparationSteps', CollectionType::class, $collectionOptions + [
                'entry_type' => TextareaType::class,
                'entry_options' => ['label' => false, 'attr' => ['rows' => 3, 'placeholder' => 'Décris cette étape']],
            ])
            ->add('tips', CollectionType::class, $collectionOptions + [
                'entry_type' => TextareaType::class,
                'entry_options' => ['label' => false, 'attr' => ['rows' => 2, 'placeholder' => 'Ex. Encore meilleur le lendemain : prépare-le à l’avance.']],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Recipe::class]);
    }
}
