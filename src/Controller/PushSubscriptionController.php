<?php

namespace App\Controller;

use App\Entity\PushSubscription;
use App\Entity\User;
use App\Repository\PushSubscriptionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class PushSubscriptionController extends AbstractController
{
    #[Route('/account/push-subscription', name: 'app_push_subscription_save', methods: ['POST'])]
    public function save(Request $request, PushSubscriptionRepository $subscriptionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $payload = $request->toArray();
        if (!$this->isCsrfTokenValid('push_subscription', (string) ($payload['csrfToken'] ?? ''))) {
            return $this->json(['error' => 'Jeton de sécurité invalide.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $endpoint = trim((string) ($payload['endpoint'] ?? ''));
        $subscriptionKeys = is_array($payload['keys'] ?? null) ? $payload['keys'] : [];
        $publicKey = trim((string) ($subscriptionKeys['p256dh'] ?? ''));
        $authenticationToken = trim((string) ($subscriptionKeys['auth'] ?? ''));
        if (!str_starts_with($endpoint, 'https://') || $publicKey === '' || $authenticationToken === '') {
            return $this->json(['error' => 'Abonnement Web Push invalide.'], JsonResponse::HTTP_UNPROCESSABLE_ENTITY);
        }

        $pushSubscription = $subscriptionRepository->findOneByEndpoint($endpoint) ?? new PushSubscription();
        $pushSubscription
            ->setUser($user)
            ->setEndpoint($endpoint)
            ->setPublicKey($publicKey)
            ->setAuthenticationToken($authenticationToken)
            ->setContentEncoding('aes128gcm');
        $entityManager->persist($pushSubscription);
        $entityManager->flush();

        return $this->json(['saved' => true]);
    }

    #[Route('/account/push-subscription', name: 'app_push_subscription_delete', methods: ['DELETE'])]
    public function delete(Request $request, PushSubscriptionRepository $subscriptionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $user = $this->getAuthenticatedUser();
        $payload = $request->toArray();
        if (!$this->isCsrfTokenValid('push_subscription', (string) ($payload['csrfToken'] ?? ''))) {
            return $this->json(['error' => 'Jeton de sécurité invalide.'], JsonResponse::HTTP_FORBIDDEN);
        }

        $pushSubscription = $subscriptionRepository->findOneByEndpoint(trim((string) ($payload['endpoint'] ?? '')));
        if ($pushSubscription !== null && $pushSubscription->getUser() === $user) {
            $entityManager->remove($pushSubscription);
            $entityManager->flush();
        }

        return $this->json(['deleted' => true]);
    }

    private function getAuthenticatedUser(): User
    {
        $user = $this->getUser();
        if (!$user instanceof User) {
            throw $this->createAccessDeniedException();
        }

        return $user;
    }
}
