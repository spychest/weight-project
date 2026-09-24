<?php

namespace App\Form;

use App\Entity\Profile;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\File;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('displayName', TextType::class, [
                'label' => 'Comment souhaites-tu être appelé(e) ?',
                'attr' => ['placeholder' => 'Ex. Camille'],
                'constraints' => [
                    new NotBlank(message: 'Choisis un nom d’affichage.'),
                    new Length(max: 100),
                ],
            ])
            ->add('avatar', FileType::class, [
                'label' => 'Photo de profil',
                'mapped' => false,
                'required' => false,
                'help' => 'JPG, PNG ou WebP, 5 Mo maximum. Une nouvelle photo remplace l’ancienne.',
                'constraints' => [
                    new File(
                        maxSize: '5M',
                        mimeTypes: ['image/jpeg', 'image/png', 'image/webp'],
                        mimeTypesMessage: 'Sélectionne une image JPG, PNG ou WebP valide.',
                    ),
                ],
            ])
            ->add('removeAvatar', CheckboxType::class, [
                'label' => 'Supprimer ma photo personnalisée',
                'mapped' => false,
                'required' => false,
            ])
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
