<?php

namespace App\EventSubscriber;

use App\Entity\User;
use App\Service\ActivityLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;
use Symfony\Component\Security\Http\Event\LogoutEvent;

class SecurityActivitySubscriber implements EventSubscriberInterface
{
    public function __construct(private ActivityLogger $logger) {}

    public static function getSubscribedEvents(): array
    {
        return [
            LoginSuccessEvent::class => 'onLoginSuccess',
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function onLoginSuccess(LoginSuccessEvent $event): void
    {
        $user = $event->getAuthenticatedToken()->getUser();
        if ($user instanceof User) {
            $this->logger->log(
                action: 'LOGIN',
                actor: $user,
                entityType: 'User',
                entityId: $user->getId(),
                description: 'User logged in'
            );
        }
    }

    public function onLogout(LogoutEvent $event): void
    {
        $user = $event->getToken() ? $event->getToken()->getUser() : null;
        if ($user instanceof User) {
            $this->logger->log(
                action: 'LOGOUT',
                actor: $user,
                entityType: 'User',
                entityId: $user->getId(),
                description: 'User logged out'
            );
        }
    }
}
