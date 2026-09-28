<?php

namespace App\Form;

use App\Entity\MotivationPoint;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class MotivationPointType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('content', TextareaType::class, [
            'label' => 'Ma motivation',
            'attr' => [
                'maxlength' => 500,
                'rows' => 4,
                'placeholder' => 'Ex. Je me sentirai plus libre et fier de mes choix.',
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => MotivationPoint::class]);
    }
}
