<?php
namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class UpdateCustomerResolver implements MutationResolverInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
    ) {}

    public function __invoke(?object $item, array $context): object
    {
        $input = $context['args']['input'] ?? [];
        $userId = $input['id'] ?? null;

        if (!$userId) {
            throw new NotFoundHttpException('Falta el ID del usuario.');
        }

        $user = $this->em->getRepository(User::class)->find($userId);
        if (!$user) {
            throw new NotFoundHttpException('Usuario no encontrado.');
        }

        $currentUser = $context['user'] ?? null;
        if (!$currentUser) {
            throw new AccessDeniedHttpException('Usuario no autenticado.');
        }

        // Solo el propio usuario o un admin puede editar
        if ($currentUser->getId() !== $user->getId() && !in_array('ROLE_ADMIN', $currentUser->getRoles(), true)) {
            throw new AccessDeniedHttpException('No tienes permisos para editar este usuario.');
        }

        // Actualizar email si fue enviado
        if (!empty($input['email'])) {
            $user->setEmail($input['email']);
        }

        // Cambiar contraseña si fue enviada
        if (!empty($input['password'])) {
            $hashed = $this->passwordHasher->hashPassword($user, $input['password']);
            $user->setPassword($hashed);
        }

        // Actualizar datos del perfil
        $profileInput = $input['profile'] ?? [];
        $profile = $user->getProfile();

        if ($profile) {
            if (isset($profileInput['firstName'])) {
                $profile->setFirstName($profileInput['firstName']);
            }
            if (isset($profileInput['lastName'])) {
                $profile->setLastName($profileInput['lastName']);
            }
            if (isset($profileInput['displayName'])) {
                $profile->setDisplayName($profileInput['displayName']);
            }
            if (isset($profileInput['description'])) {
                $profile->setDescription($profileInput['description']);
            }
        }

        $this->em->flush();

        return $user;
    }
}
