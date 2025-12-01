<?php
namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Image;
use App\Security\EmailVerifier;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateUserWithProfileResolver implements MutationResolverInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerifier $emailVerifier
    ) {}

    public function __invoke(?object $item, array $context): object
    {
        // El payload de Api Platform para GraphQL viene en $context['args']['input']
        $input = $context['args']['input'] ?? [];

        $email = $input['email'] ?? null;
        $plainPassword = $input['password'] ?? null;
        $roles = $input['roles'] ?? [];
        $enabled = array_key_exists('enabled', $input) ? (bool)$input['enabled'] : true;
        $profileInput = $input['profile'] ?? null;

        if (!$email || !$plainPassword || !$profileInput) {
            throw new BadRequestHttpException('Faltan campos obligatorios: email, password, profile.');
        }

        // Crear entidades
        $user = new User();
        $user->setEmail($email);
        $user->setRoles($roles);
        $user->setEnabled($enabled);

        // Hash de password
        $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        $user->setPassword($hashed);

        // Construir el perfil
        $profile = new UserProfile();
        $profile->setFirstName($profileInput['firstName'] ?? '');
        $profile->setLastName($profileInput['lastName'] ?? '');
        $profile->setCompanyRole($profileInput['companyRole'] ?? null);
        $profile->setDisplayName($profileInput['displayName'] ?? null);
        $profile->setDescription($profileInput['description'] ?? null);

        // Si el campo image es NOT NULL en UserProfile, resolvemos imageId
        if (array_key_exists('imageId', $profileInput) && $profileInput['imageId']) {
            $image = $this->em->getRepository(Image::class)->find($profileInput['imageId']);
            if (!$image) {
                throw new BadRequestHttpException('imageId inválido.');
            }
            $profile->setImage($image);
        }

        $user->setProfile($profile);

        // Persistir atómicamente
        $this->em->persist($profile);
        $this->em->persist($user);
        $this->em->flush();

        // Enviar email de verificación al customer creado
        $this->emailVerifier->sendApiEmailConfirmation($user);

        return $user;
    }
}
