<?php
namespace App\EventSubscriber;

use App\Entity\User;
use Lexik\Bundle\JWTAuthenticationBundle\Event\JWTCreatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class JWTCreatedSubscriber implements EventSubscriberInterface
{
    public function onJWTCreated(JWTCreatedEvent $event): void
    {
        /** @var User $user */
        $user = $event->getUser();

        $payload = $event->getData();

        if ($user->getCustomer()) {
            $payload['customer_id'] = $user->getCustomer()->getId();
        }
        if ($user->getMerchant()) {
            $payload['merchant_id'] = $user->getMerchant()->getId();
        }

        $event->setData($payload);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            JWTCreatedEvent::class => 'onJWTCreated',
        ];
    }
}
