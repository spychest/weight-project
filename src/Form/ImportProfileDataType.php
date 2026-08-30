<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

final class ImportProfileDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('backupFile', FileType::class, [
            'label' => 'Fichier de sauvegarde JSON',
            'constraints' => [
                new Assert\NotNull(message: 'Sélectionne un fichier JSON.'),
                new Assert\File(maxSize: '10M', extensions: ['json'], extensionsMessage: 'Le fichier doit être au format JSON.'),
            ],
        ])->add('confirmReplacement', CheckboxType::class, [
            'label' => 'Je comprends que les données actuelles de mon profil seront remplacées par celles du fichier.',
            'constraints' => [new Assert\IsTrue(message: 'Tu dois confirmer le remplacement des données.')],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['csrf_token_id' => 'import_profile_data']);
    }
}
