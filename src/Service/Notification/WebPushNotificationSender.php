<?php

namespace App\Service\Notification;

use App\Entity\PushSubscription as PushSubscriptionEntity;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Minishlink\WebPush\Subscription;
use Minishlink\WebPush\WebPush;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final readonly class WebPushNotificationSender
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        #[Autowire('%env(WEB_PUSH_VAPID_SUBJECT)%')]
        private string $vapidSubject,
        #[Autowire('%env(WEB_PUSH_VAPID_PUBLIC_KEY)%')]
        private string $vapidPublicKey,
        #[Autowire('%env(WEB_PUSH_VAPID_PRIVATE_KEY)%')]
        private string $vapidPrivateKey,
    ) {
    }

    public function isConfigured(): bool
    {
        return !str_contains($this->vapidPublicKey, 'CHANGE_ME')
            && !str_contains($this->vapidPrivateKey, 'CHANGE_ME')
            && $this->vapidPublicKey !== ''
            && $this->vapidPrivateKey !== '';
    }

    public function getPublicKey(): string
    {
        return $this->isConfigured() ? $this->vapidPublicKey : '';
    }

    public function sendReminder(User $user): int
    {
        if (!$this->isConfigured() || !$user->isNotificationsEnabled()) {
            return 0;
        }

        $webPush = new WebPush([
            'VAPID' => [
                'subject' => $this->vapidSubject,
                'publicKey' => $this->vapidPublicKey,
                'privateKey' => $this->vapidPrivateKey,
            ],
        ]);
        $webPush->setReuseVAPIDHeaders(true);

        $payload = json_encode([
            'title' => 'Un petit moment pour toi',
            'body' => 'Pense à compléter ton suivi lorsque tu en as envie.',
            'url' => '/dashboard',
        ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        foreach ($user->getPushSubscriptions() as $pushSubscription) {
            $webPush->queueNotification($this->createLibrarySubscription($pushSubscription), $payload, [
                'TTL' => 3600,
                'urgency' => 'low',
                'topic' => 'tracking-reminder',
            ]);
        }

        $successfulNotificationCount = 0;
        foreach ($webPush->flush() as $notificationReport) {
            if ($notificationReport->isSuccess()) {
                ++$successfulNotificationCount;
                continue;
            }

            if ($notificationReport->isSubscriptionExpired()) {
                $expiredSubscription = $this->findSubscriptionByEndpoint($user, $notificationReport->getEndpoint());
                if ($expiredSubscription !== null) {
                    $this->entityManager->remove($expiredSubscription);
                }
            }
        }

        return $successfulNotificationCount;
    }

    private function createLibrarySubscription(PushSubscriptionEntity $pushSubscription): Subscription
    {
        return new Subscription(
            $pushSubscription->getEndpoint(),
            $pushSubscription->getPublicKey(),
            $pushSubscription->getAuthenticationToken(),
            $pushSubscription->getContentEncoding(),
        );
    }

    private function findSubscriptionByEndpoint(User $user, string $endpoint): ?PushSubscriptionEntity
    {
        foreach ($user->getPushSubscriptions() as $pushSubscription) {
            if ($pushSubscription->getEndpoint() === $endpoint) {
                return $pushSubscription;
            }
        }

        return null;
    }
}
