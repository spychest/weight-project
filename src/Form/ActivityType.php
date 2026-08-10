<?php

namespace App\Form;

use App\Entity\Activity;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActivityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'label' => 'Date',
                'widget' => 'single_text',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Activité',
                'help' => 'Décris librement l’activité : type, durée, distance, quantité, etc.',
            ])
            ->add('painLevel', IntegerType::class, [
                'label' => 'Douleur (1-10)',
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
            'data_class' => Activity::class,
        ]);
    }
}