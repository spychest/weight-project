<?php

namespace App\Tests\Functional;

use App\Entity\FavoriteMeal;
use App\Entity\FoodEvent;
use App\Form\FoodEventType;
use App\Kernel;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Twig\Environment;

final class FoodEventFavoriteTemplateTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    #[Test]
    public function favoriteMealCanPopulateAnEditableMealDescription(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $requestStack = $container->get(RequestStack::class);
        $requestStack->push(Request::create('/food/new'));
        $favoriteMeal = (new FavoriteMeal())->setName('Déjeuner habituel')->setDescription('Poulet, riz et légumes');
        $reflection = new \ReflectionProperty($favoriteMeal, 'id');
        $reflection->setValue($favoriteMeal, 42);

        $form = $container->get(FormFactoryInterface::class)->create(FoodEventType::class, new FoodEvent(), [
            'csrf_protection' => false,
            'favorite_meals' => [$favoriteMeal],
        ]);
        $renderedForm = $container->get(Environment::class)->render('food_event/new.html.twig', [
            'form' => $form->createView(),
            'editMod' => false,
        ]);

        self::assertStringContainsString('Déjeuner habituel', $renderedForm);
        self::assertStringContainsString('data-meal-description="Poulet, riz et légumes"', $renderedForm);
        self::assertStringContainsString('Enregistrer ce repas dans mes favoris', $renderedForm);
        self::assertStringContainsString('data-food-event-form', $renderedForm);
    }
}
