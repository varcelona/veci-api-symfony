<?php

namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\GraphQL\Resource\Auth;
use App\Service\RefreshTokenGenerator;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Doctrine\ORM\EntityManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class ConfirmPasswordResetResolver implements MutationResolverInterface
{
    public function __construct(
        private ResetPasswordHelperInterface $resetHelper,
        private UserPasswordHasherInterface $hasher,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private EntityManagerInterface $em,
        private TranslatorInterface $translator,
    ) {}

    public function __invoke(?object $item, array $context): ?object
    {
        $token       = $context['args']['input']['token'] ?? null;
        $newPassword = $context['args']['input']['password'] ?? null;

        // Siempre trabajamos con un Auth
        $auth = $item instanceof Auth ? $item : new Auth();
        $auth->success = false;
        $auth->message = $this->translator->trans('ui.auth.missing_token');

        if (!$token || !$newPassword) {
            return $auth;
        }

        try {
            $user = $this->resetHelper->validateTokenAndFetchUser($token);
        } catch (\Throwable $e) {
            $auth->success = false;
            $auth->message = $this->translator->trans('ui.auth.invalid_expired_token');
            return $auth;
        }

        // Cambiar password
        $hashed = $this->hasher->hashPassword($user, $newPassword);
        $user->setPassword($hashed);

        // Invalidar el token de reset
        $this->resetHelper->removeResetRequest($token);
        $this->em->flush();

        // Genera nuevo token y refresh token
        $jwt = $this->jwtManager->create($user);
        $refresh = $this->refreshTokenGenerator->generate($user);
            $user->setRefreshToken($refresh);

        $auth->jwt = $jwt;
        $auth->refreshToken = $refresh;

        $auth->success = true;
        $auth->message = $this->translator->trans('ui.auth.password_updated');

        return $auth;
    }
}
