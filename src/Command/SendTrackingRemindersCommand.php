<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use App\Service\Notification\WebPushNotificationSender;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'project:notifications:send', description: 'Envoie les rappels de suivi Web Push arrivés à échéance.')]
final class SendTrackingRemindersCommand extends Command
{
    private const FREQUENCY_INTERVALS = [
        'daily' => 'P1D',
        'three_times_weekly' => 'P2D',
        'weekly' => 'P7D',
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly WebPushNotificationSender $notificationSender,
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        if (!$this->notificationSender->isConfigured()) {
            $io->error('Les clés VAPID Web Push ne sont pas configurées.');

            return Command::FAILURE;
        }

        $now = new \DateTimeImmutable();
        $sentNotificationCount = 0;
        foreach ($this->userRepository->findUsersWithEnabledNotifications() as $user) {
            if (!$this->isReminderDue($user, $now)) {
                continue;
            }

            $sentForUser = $this->notificationSender->sendReminder($user);
            if ($sentForUser > 0) {
                $user->markNotificationSentAt($now);
                $sentNotificationCount += $sentForUser;
            }
        }

        $this->entityManager->flush();
        $io->success(sprintf('%d notification(s) envoyée(s).', $sentNotificationCount));

        return Command::SUCCESS;
    }

    private function isReminderDue(User $user, \DateTimeImmutable $now): bool
    {
        $lastSentAt = $user->getLastNotificationSentAt();
        if ($lastSentAt === null) {
            return true;
        }

        $intervalSpecification = self::FREQUENCY_INTERVALS[$user->getNotificationFrequency()] ?? self::FREQUENCY_INTERVALS['weekly'];

        return $lastSentAt->add(new \DateInterval($intervalSpecification)) <= $now;
    }
}
