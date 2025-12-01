<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

class UserVerifyVoter extends Voter
{
    public const VERIFY = 'USER_VERIFY';

    public function __construct(private Security $security) {}

    protected function supports(string $attribute, $subject): bool
    {
        return $attribute === self::VERIFY && $subject instanceof User;
    }

    protected function voteOnAttribute(string $attribute, $userToVerify, TokenInterface $token): bool
    {
        $currentUser = $token->getUser();

        if (!$currentUser instanceof User) {
            return false;
        }

        // 1. ROLE_ADMIN puede verificar a cualquiera
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return true;
        }

        // 2. ROLE_MERCHANT solo a sí mismo (o asociados si lo amplias después)
        if ($this->security->isGranted('ROLE_MERCHANT') &&
            $currentUser->getId() === $userToVerify->getId()
        ) {
            return true;
        }

        // 3. El usuario autenticado solo puede verificarse a sí mismo
        return $currentUser->getId() === $userToVerify->getId();
    }
}
