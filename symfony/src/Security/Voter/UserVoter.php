<?php

namespace App\Security\Voter;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Bundle\SecurityBundle\Security;

class UserVoter extends Voter
{
    public const MANAGE_ALL_USERS = 'MANAGE_ALL_USERS';
    public const MANAGE_SELF = 'MANAGE_SELF';
    public const MANAGE_SELF_LIMITED = 'MANAGE_SELF_LIMITED';

    public function __construct(
        private Security $security
    ){
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [
        self::MANAGE_ALL_USERS,
        self::MANAGE_SELF,
        self::MANAGE_SELF_LIMITED,
        ], true)
        && (
            $subject === null
            || $subject instanceof User
            || \in_array($attribute, [self::MANAGE_SELF, self::MANAGE_SELF_LIMITED], true)
        );
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $actor = $token->getUser();
        if (!$actor instanceof UserInterface) {
            return false;
        }

        // Admin global puede todo
        if ($this->security->isGranted(User::ROLE_ADMIN)) {
            return true;
        }

        return match ($attribute) {
            self::MANAGE_ALL_USERS    => false, // sólo ROLE_ADMIN
            self::MANAGE_SELF         => $subject instanceof User && $this->isSelf($actor, $subject),
            self::MANAGE_SELF_LIMITED => $subject instanceof User && $this->isSelf($actor, $subject),
            default                   => false,
        };
    }

    private function isSelf(UserInterface $actor, User $subject): bool
    {
        return $actor instanceof User && $actor->getId() === $subject->getId();
    }
}
