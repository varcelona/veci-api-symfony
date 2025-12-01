<?php

namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Gesdinet\JWTRefreshTokenBundle\Entity\RefreshToken;


class RefreshTokenGenerator
{
    private int $ttl;

    public function __construct(
        private RefreshTokenManagerInterface $refreshTokenManager,
        private EntityManagerInterface $em,
        int $refreshTokenTtl = 2592000 // 30 días
    ) {
        $this->ttl = $refreshTokenTtl;
    }

    public function generate(User $user): string
    {
        $repo = $this->em->getRepository(RefreshToken::class);
        // 1) Borrar todos los refresh tokens anteriores del usuario
        $existingTokens = $repo->findBy(['username' => $user->getUserIdentifier()]);

        foreach ($existingTokens as $oldToken) {
            $this->refreshTokenManager->delete($oldToken); // correcto
        }

        // 2) Crear un refresh token nuevo compatible con TODAS las versiones
        /** @var RefreshToken $refreshToken */
        $refreshToken = $this->refreshTokenManager->create();

        $refreshToken->setUsername($user->getUserIdentifier());
        $refreshToken->setRefreshToken(bin2hex(random_bytes(64)));
        $refreshToken->setValid(new \DateTimeImmutable(sprintf('+%d seconds', $this->ttl)));

        // El token string se genera automáticamente al guardar
        $this->refreshTokenManager->save($refreshToken);

        return $refreshToken->getRefreshToken();
    }
}
