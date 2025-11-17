<?php

namespace App\GraphQL\Resolver;

use App\Entity\User;
use App\Entity\UserProfile;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;

class UpdateCustomerResolver implements MutationResolverInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function __invoke(?object $item, array $context): object
    {
        $args = $context['args']['input'];
        $user = $this->em->getRepository(User::class)->find($args['id']);

        if (!$user) {
            throw new \Exception('User not found');
        }

        if (isset($args['email'])) {
            $user->setEmail($args['email']);
        }

        if (isset($args['password'])) {
            $user->setPassword(
                $this->passwordHasher->hashPassword($user, $args['password'])
            );
        }

        // Actualizar perfil embebido
        if (isset($args['profile'])) {
            $profileData = $args['profile'];
            $profile = $user->getProfile() ?? new UserProfile();

            $profile
                ->setFirstName($profileData['firstName'] ?? $profile->getFirstName())
                ->setLastName($profileData['lastName'] ?? $profile->getLastName())
                ->setDisplayName($profileData['displayName'] ?? $profile->getDisplayName())
                ->setDescription($profileData['description'] ?? $profile->getDescription());

            $user->setProfile($profile);
            $this->em->persist($profile);
        }

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }
}
