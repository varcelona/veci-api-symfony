<?php

namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Repository\UserRepository;
use App\Service\RefreshTokenGenerator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
class LoginUserResolver implements MutationResolverInterface
{
    public function __construct(
        private UserRepository $userRepository,
        private UserPasswordHasherInterface $passwordHasher,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private LoggerInterface $logger
    ) {

    }

    public function __invoke(?object $item, array $context): ?object
    {
        $input = $context['args']['input'] ?? [];

        $email = $input['email'] ?? null;
        $password = $input['password'] ?? null;

        if (!$email || !$password) {
            throw new BadRequestHttpException('Email and password are required.');
        }

        $user = $this->userRepository->findOneBy(['email' => $email]);

        if (!$user || !$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new BadRequestHttpException('Invalid credentials.');
        }

        // chequea enabled
        if (!$user->isEnabled()) { throw new BadRequestHttpException('User disabled'); }

        try {
            $jwt = $this->jwtManager->create($user);
            $user->setJwt($jwt);
        } catch (\Throwable $e) {
            $this->logger->error('JWT generation failed', [
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            // Continuamos sin token
            $user->setJwt(null);
        }
        try {
            $refresh = $this->refreshTokenGenerator->generate($user);
            $user->setRefreshToken($refresh);
        } catch (\Throwable $e) {
            $this->logger->error('Refresh Token generation failed', [
                'email' => $user->getEmail(),
                'error' => $e->getMessage(),
            ]);

            // Continuamos sin refresh token
            $user->setRefreshToken(null);
        }

        return $user;
    }
}
