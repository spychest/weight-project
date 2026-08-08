<?php

namespace App\Form;

use App\Entity\SleepEntry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SleepEntryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('date', null, [
                'label' => 'Date du réveil',
            ])
            ->add('bedTime', null, [
                'label' => 'Heure du coucher',
            ])
            ->add('wakeUpTime', null, [
                'label' => 'Heure du réveil',
            ])
            ->add('quality', IntegerType::class, [
                'label' => 'Qualité du sommeil (1-10)',
            ])
            ->add('note', TextareaType::class, [
                'label' => 'Notes',
                'required' => false,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SleepEntry::class,
        ]);
    }
}