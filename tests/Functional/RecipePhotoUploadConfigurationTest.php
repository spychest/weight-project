<?php

namespace App\Tests\Functional;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Kernel;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Validator\Constraints\Image;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class RecipePhotoUploadConfigurationTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    #[Test]
    public function recipePhotoAcceptsSupportedImagesUpToFiveMegabytes(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $formFactory = $container->get(FormFactoryInterface::class);
        $validator = $container->get(ValidatorInterface::class);
        self::assertInstanceOf(FormFactoryInterface::class, $formFactory);
        self::assertInstanceOf(ValidatorInterface::class, $validator);

        $photoField = $formFactory->create(RecipeType::class, new Recipe())->get('photo');
        $constraints = $photoField->getConfig()->getOption('constraints');
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Image::class, $constraints[0]);
        self::assertSame(5_000_000, $constraints[0]->maxSize);
        self::assertSame(['image/jpeg', 'image/png', 'image/webp'], $constraints[0]->mimeTypes);

        $temporaryImagePath = tempnam(sys_get_temp_dir(), 'recipe-photo-');
        self::assertIsString($temporaryImagePath);

        try {
            $pngContents = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=', true);
            self::assertIsString($pngContents);
            file_put_contents($temporaryImagePath, $pngContents.str_repeat("\0", 4_000_000));
            $uploadedPhoto = new class($temporaryImagePath, 'recette.png', 'image/png', null, true) extends UploadedFile {
                public function getMimeType(): string
                {
                    return 'image/png';
                }
            };

            self::assertCount(0, $validator->validate($uploadedPhoto, $constraints[0]));
        } finally {
            if (is_file($temporaryImagePath)) {
                unlink($temporaryImagePath);
            }
        }
    }
}
