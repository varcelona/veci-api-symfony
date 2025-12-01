<?php

namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Security\Voter\UserVerifyVoter;
use Symfony\Bundle\SecurityBundle\Security;

final class SendUserVerificationEmailResolver implements MutationResolverInterface
{
    public function __construct(
        private Security $security,
        private EmailVerifier $emailVerifier,
        private UserRepository $userRepository
    ) {}

    public function __invoke(?object $item, array $context): object
    {
        $id = $context['args']['input']['id'];
        $user = $this->userRepository->find($id);

        $this->security->isGranted(UserVerifyVoter::VERIFY, $user)
            ?: throw new \Exception('Forbidden');

        $this->emailVerifier->sendApiEmailConfirmation($user);

        return $user;
    }
}
