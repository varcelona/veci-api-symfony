<?php

namespace App\Security\Voter;

use App\Entity\Store;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class StoreVoter extends Voter
{
    public const MANAGE_ALL = 'MANAGE_ALL_STORE';
    public const MANAGE_OWN = 'MANAGE_OWN_STORE';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::MANAGE_ALL, self::MANAGE_OWN], true)
            && $subject instanceof Store;
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token
    ): bool {
        $user = $token->getUser();

        if (!$user instanceof User) {
            return false;
        }

        // ADMIN → pasa todo
        if (in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Merchant → solo stores asignadas
        if (
            $attribute === self::MANAGE_OWN &&
            in_array('ROLE_MERCHANT', $user->getRoles(), true) &&
            $user->getMerchant() &&
            $subject->getMerchants()->contains($user->getMerchant())
        ) {
            return true;
        }

        return false;
    }
}
