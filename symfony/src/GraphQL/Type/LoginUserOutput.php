<?php

namespace App\GraphQL\Type;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use Symfony\Component\Serializer\Annotation\Groups;

#[ApiResource(
    graphQlOperations: [
        new Mutation(name: 'loginUser')
    ]
)]
class LoginUserOutput
{
    #[Groups(['user:read'])]
    public string $token;

    #[Groups(['user:read'])]
    public ?\App\Entity\User $user = null;
}
