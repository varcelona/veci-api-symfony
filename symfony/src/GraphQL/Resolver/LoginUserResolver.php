<?php

namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class LoginUserResolver implements MutationResolverInterface
{
    private $passwordHasher;
    private $jwtManager;
    private $userRepository;

    public function __construct(
        UserRepository $userRepository,
        UserPasswordHasherInterface $passwordHasher,
        JWTTokenManagerInterface $jwtManager
    ) {
        $this->userRepository = $userRepository;
        $this->passwordHasher = $passwordHasher;
        $this->jwtManager = $jwtManager;
    }

    public function __invoke(?object $item, array $context): ?object
    {
        $args = $context['args']['input'];
        $email = $args['email'];
        $password = $args['password'];

        $user = $this->userRepository->findOneBy(['email' => $email]);


        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            throw new \Exception('Invalid credentials');
        }

        $token = $this->jwtManager->create($user);

        // return [
        //     'token' => $token,
        //     'user' => $user,
        // ];
        return $token;
    }
}
