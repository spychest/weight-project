<?php

namespace App\Form;

use App\Entity\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('height', NumberType::class, [
                'label' => 'Taille (cm)',
                'scale' => 1,
            ])
            ->add('birthDate', DateType::class, [
                'label' => 'Date de naissance',
                'widget' => 'single_text',
            ])
            ->add('biologicalGender', ChoiceType::class, [
                'label' => 'Sexe biologique',
                'choices' => [
                    'Homme' => 'male',
                    'Femme' => 'female',
                ],
                'placeholder' => 'Choisir',
            ])
            ->add('startingWeight', NumberType::class, [
                'label' => 'Poids de départ (kg)',
                'scale' => 1,
            ])
            ->add('targetWeight', NumberType::class, [
                'label' => 'Poids objectif (kg)',
                'scale' => 1,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Profile::class,
        ]);
    }
}