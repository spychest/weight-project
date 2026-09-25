<?php

namespace App\Tests\Functional;

use App\Entity\Recipe;
use App\Form\RecipeType;
use App\Kernel;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

final class RecipeTemplateTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    #[Test]
    public function recipeFormRendersEveryModularCollection(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $formFactory = $container->get(FormFactoryInterface::class);
        $twig = $container->get(Environment::class);
        $requestStack = $container->get(RequestStack::class);

        self::assertInstanceOf(FormFactoryInterface::class, $formFactory);
        self::assertInstanceOf(Environment::class, $twig);
        self::assertInstanceOf(RequestStack::class, $requestStack);
        $requestStack->push(Request::create('/recipes/new'));

        $recipe = (new Recipe())->setTitle('Recette de test');
        $renderedForm = $twig->render('recipe/form.html.twig', [
            'form' => $formFactory->create(RecipeType::class, $recipe, ['csrf_protection' => false])->createView(),
            'recipe' => $recipe,
            'editMode' => false,
        ]);

        self::assertStringContainsString('data-collection="utensils"', $renderedForm);
        self::assertStringContainsString('data-collection="ingredients"', $renderedForm);
        self::assertStringContainsString('data-collection="setupSteps"', $renderedForm);
        self::assertStringContainsString('data-collection="preparationSteps"', $renderedForm);
        self::assertStringContainsString('data-collection="tips"', $renderedForm);
        self::assertStringContainsString('name="recipe[vegetarian]"', $renderedForm);
        self::assertStringContainsString('name="recipe[vegan]"', $renderedForm);
        self::assertStringContainsString('name="recipe[glutenFree]"', $renderedForm);
        self::assertStringContainsString('Exclut la viande et le poisson', $renderedForm);
        self::assertStringContainsString('Exclut tous les produits d’origine animale', $renderedForm);
        self::assertStringContainsString('Exclut les céréales contenant du gluten', $renderedForm);
        self::assertStringContainsString('Publier la recette', $renderedForm);
    }

    #[Test]
    public function recipeDietaryBadgesDescribeEveryEnabledDiet(): void
    {
        self::bootKernel();
        $twig = self::getContainer()->get(Environment::class);
        self::assertInstanceOf(Environment::class, $twig);

        $recipe = (new Recipe())
            ->setVegetarian(true)
            ->setVegan(true)
            ->setGlutenFree(true);
        $renderedBadges = $twig->render('recipe/_dietary_badges.html.twig', ['recipe' => $recipe]);

        self::assertStringContainsString('Végétarien', $renderedBadges);
        self::assertStringContainsString('Végan', $renderedBadges);
        self::assertStringContainsString('Sans gluten', $renderedBadges);
    }
}
