<?php

namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use App\Security\EmailVerifier;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use App\Service\RefreshTokenGenerator;
use Psr\Log\LoggerInterface;

final class RegisterCustomerResolver implements MutationResolverInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerifier $emailVerifier,
        private JWTTokenManagerInterface $jwtManager,
        private RefreshTokenGenerator $refreshTokenGenerator,
        private LoggerInterface $logger

    ) {}

    public function __invoke(?object $item, array $context): ?object
    {
        $input = $context['args']['input'] ?? [];
        $email = $input['email'] ?? null;
        $firstname = $input['firstname'] ?? null;
        $lastname = $input['lastname'] ?? null;
        $plainPassword = $input['password'] ?? null;

        if (!$email || !$plainPassword) {
            throw new BadRequestHttpException('Email y password son obligatorios.');
        }

        // Verificar que no exista otro usuario con el mismo email
        $existingUser = $this->em->getRepository(User::class)->findOneBy(['email' => $email]);
        if ($existingUser) {
            throw new BadRequestHttpException('Ya existe un usuario registrado con este email.');
        }

        // Crear el usuario base
        $user = new User();
        $user->setEmail($email);
        $user->setEnabled(true);
        $user->setRoles(['ROLE_CUSTOMER']);
        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);

        // Crear un perfil vacío o mínimo
        $profile = new UserProfile();
        $profile->setFirstName($firstname);
        $profile->setLastName($lastname);
        $user->setProfile($profile);

        // Crear la entidad Customer
        $customer = new Customer();
        $customer->setUser($user);
        $user->setCustomer($customer);

        // Persistir todo
        $this->em->persist($profile);
        $this->em->persist($customer);
        $this->em->persist($user);
        $this->em->flush();

        // Enviar email de verificación al customer creado
        $this->emailVerifier->sendApiEmailConfirmation($user);

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
