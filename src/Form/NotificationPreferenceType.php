<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

final class NotificationPreferenceType extends AbstractType
{
    public const FREQUENCY_LABELS = [
        'Tous les jours' => 'daily',
        'Tous les 2 jours (environ 3 fois par semaine)' => 'three_times_weekly',
        'Une fois par semaine' => 'weekly',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('notificationsEnabled', CheckboxType::class, [
                'label' => 'Activer les rappels',
                'required' => false,
            ])
            ->add('notificationFrequency', ChoiceType::class, [
                'label' => 'Fréquence des rappels',
                'choices' => self::FREQUENCY_LABELS,
            ]);
    }
}
