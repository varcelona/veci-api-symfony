<?php
namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\MutationResolverInterface;
use App\Entity\User;
use App\Entity\UserProfile;
use App\Entity\Image;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateUserWithProfileResolver implements MutationResolverInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private UserPasswordHasherInterface $passwordHasher,
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
        } else {
            // Si tu schema exige imagen no nula, acá deberías:
            // - o lanzar error, o
            // - o crear una imagen por defecto
            // throw new BadRequestHttpException('imageId es obligatorio.');
        }

        // Setear relación 1-1
        $user->setProfile($profile);

        // Persistir atómicamente
        $this->em->persist($profile);
        $this->em->persist($user);
        $this->em->flush();

        return $user; // Api Platform serializa al tipo User


        
        // // El payload de Api Platform para GraphQL viene en $context['args']['input']
        // $input = $context['args']['input'] ?? [];

        // $email = $input['email'] ?? null;
        // $plainPassword = $input['password'] ?? null;
        // $roles = $input['roles'] ?? [];
        // $enabled = array_key_exists('enabled', $input) ? (bool)$input['enabled'] : true;
        // $firstName   = $input['firstName'] ?? null;
        // $lastName    = $input['lastName'] ?? null;
        // $imageId     = $input['imageId'] ?? null;

        // if (!$email || !$plainPassword || !$firstName || !$lastName) {
        //     throw new BadRequestHttpException('Faltan campos obligatorios: email, password, firstname, lastname.');
        // }

        // // Crear entidades
        // $user = new User();
        // $user->setEmail($email);
        // $user->setRoles($roles);
        // $user->setEnabled($enabled);

        // // Hash de password
        // $hashed = $this->passwordHasher->hashPassword($user, $plainPassword);
        // $user->setPassword($hashed);

        // // Construir el perfil
        // $profile = new UserProfile();
        // $profile->setFirstName($firstName ?? '');
        // $profile->setLastName($lastName ?? '');
        // $profile->setCompanyRole($input['companyRole'] ?? null);
        // $profile->setDisplayName($input['displayName'] ?? null);
        // $profile->setDescription($input['description'] ?? null);

        // // Si el campo image es NOT NULL en UserProfile, resolvemos imageId
        // if ($imageId) {
        //     $image = $this->em->getRepository(Image::class)->find($imageId);
        //     if (!$image) {
        //         throw new BadRequestHttpException('imageId inválido.');
        //     }
        //     $profile->setImage($image);
        // } else {
        //     // Si tu schema exige imagen no nula, acá deberías:
        //     // - o lanzar error, o
        //     // - o crear una imagen por defecto
        //     // throw new BadRequestHttpException('imageId es obligatorio.');
        // }

        // // Setear relación 1-1
        // $user->setProfile($profile);

        // // Persistir atómicamente
        // $this->em->persist($profile);
        // $this->em->persist($user);
        // $this->em->flush();

        // return $user; // Api Platform serializa al tipo User
    }
}
