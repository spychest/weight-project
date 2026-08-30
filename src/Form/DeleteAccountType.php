<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;

final class DeleteAccountType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('currentPassword', PasswordType::class, [
            'label' => 'Mot de passe actuel',
            'required' => $options['password_confirmation_required'],
        ])->add('confirmDeletion', CheckboxType::class, [
            'label' => 'Je comprends que mon compte et toutes mes données seront définitivement supprimés.',
            'constraints' => [new IsTrue(message: 'Tu dois confirmer la suppression définitive.')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['password_confirmation_required' => true]);
        $resolver->setAllowedTypes('password_confirmation_required', 'bool');
    }
}
