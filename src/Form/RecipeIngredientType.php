<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class RecipeIngredientType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Ingrédient',
                'attr' => [
                    'placeholder' => 'Ex. Farine',
                    'autocomplete' => 'off',
                    'data-ingredient-autocomplete' => '',
                    'data-suggestions-url' => '/ingredients/suggestions',
                ],
            ])
            ->add('quantity', NumberType::class, ['label' => 'Quantité', 'scale' => 2, 'attr' => ['min' => 0, 'step' => '0.01', 'placeholder' => '250']])
            ->add('unit', TextType::class, ['label' => 'Unité', 'attr' => ['placeholder' => 'g, ml, pièce…']]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
