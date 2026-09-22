<?php

namespace App\Tests\Functional;

use App\DTO\DashboardData;
use App\Kernel;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

final class MilestoneCelebrationTemplateTest extends KernelTestCase
{
    protected static function createKernel(array $options = []): Kernel
    {
        return new Kernel('test', true);
    }

    #[Test]
    public function milestoneSuccessIsDisplayedInAClosableCelebrationModal(): void
    {
        self::bootKernel();
        $container = self::getContainer();
        $request = Request::create('/dashboard');
        $session = new Session(new MockArraySessionStorage());
        $session->getFlashBag()->add('milestone_success', 'Félicitations, un jalon vient d’être franchi.');
        $request->setSession($session);
        $container->get(RequestStack::class)->push($request);

        $renderedDashboard = $container->get(Environment::class)->render('dashboard/index.html.twig', [
            'dashboard' => new DashboardData(
                height: 180,
                startingWeight: 100,
                targetWeight: 80,
                currentWeight: 90,
                biologicalGender: 'Non renseigné',
                lostWeight: 10,
                remainingWeight: 10,
                progressPercentage: 50,
                nextMilestone: null,
                milestoneProgressMarkers: [],
                recentMeals: [],
                recentDrinks: [],
                dailyCheckin: null,
                sleep: null,
                recentActivities: [],
                imc: 30.9,
                currentImc: 27.8,
                targetImc: 24.7,
            ),
        ]);

        self::assertStringContainsString('data-milestone-celebration', $renderedDashboard);
        self::assertStringContainsString('data-milestone-confetti', $renderedDashboard);
        self::assertStringContainsString('Fermer la fenêtre de félicitations', $renderedDashboard);
        self::assertStringContainsString('Quitter', $renderedDashboard);
        self::assertStringContainsString('Félicitations, un jalon vient d’être franchi.', $renderedDashboard);
        self::assertStringContainsString('js/milestone-celebration.js', $renderedDashboard);
    }
}
