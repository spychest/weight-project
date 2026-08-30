<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ChangeEmailType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('newEmail', EmailType::class, [
                'label' => 'Nouvelle adresse e-mail',
                'constraints' => [new Assert\NotBlank(), new Assert\Email()],
            ])
            ->add('currentPassword', PasswordType::class, [
                'label' => 'Mot de passe actuel',
                'required' => $options['password_confirmation_required'],
                'help' => $options['password_confirmation_required'] ? null : 'Ton identité est confirmée par ta connexion Google.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['password_confirmation_required' => true]);
        $resolver->setAllowedTypes('password_confirmation_required', 'bool');
    }
}
