<?php

namespace App\GraphQL\Resource;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GraphQl\Mutation;
use App\Entity\User;
use App\GraphQL\Resolver\ConfirmPasswordResetResolver;
use App\GraphQL\Resolver\LoginCustomerGoogleResolver;
use App\GraphQL\Resolver\RequestPasswordResetResolver;
use Symfony\Component\Serializer\Annotation\Groups;


#[ApiResource(
    normalizationContext: ['groups' => ['auth:read']],
    graphQlOperations: [
        new Mutation(
            name: 'loginCustomerGoogle',
            resolver: LoginCustomerGoogleResolver::class,
            read: false,
            write: false,
            deserialize: false,
            validate: false,
            args: [
                'idToken' => ['type' => 'String!'],
            ]
        ),
        new Mutation(
            name: 'requestPasswordReset',
            resolver: RequestPasswordResetResolver::class,
            read: false,
            write: false,
            deserialize: false,
            validate: false,
            args: [
                'email' => ['type' => 'String!']
            ]
        ),
        new Mutation(
            name: 'resetPassword',
            resolver: ConfirmPasswordResetResolver::class,
            args: [
                'token' => ['type' => 'String!'],
                'password' => ['type' => 'String!'],
            ],
            read: false,
            write: false,
            deserialize: false,
            validate: false,
            security: "is_granted('PUBLIC_ACCESS')"

        )
    ]
)]
class Auth
{
    #[Groups(['auth:read'])]
    public ?bool $success = null;

    #[Groups(['auth:read'])]
    public ?string $message = null;

    #[Groups(['auth:read'])]
    public ?string $jwt = null;

    #[Groups(['auth:read'])]
    public ?string $refreshToken = null;

    #[Groups(['auth:read'])]
    public ?User $user = null;
}
