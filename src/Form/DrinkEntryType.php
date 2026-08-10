<?php

namespace App\Form;

use App\Entity\DrinkEntry;
use App\Enum\DrinkType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class DrinkEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('drinkType', EnumType::class, [
                'class' => DrinkType::class,
                'label' => 'Boisson',
            ])
            ->add('quantity', IntegerType::class, [
                'label' => 'Quantité (ml)',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Précision',
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
            'data_class' => DrinkEntry::class,
        ]);
    }
}