<?php
namespace App\GraphQL\Resolver;

use ApiPlatform\GraphQl\Resolver\QueryItemResolverInterface;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security as SecurityBundleSecurity;

final class MeResolver implements QueryItemResolverInterface
{
    public function __construct(private SecurityBundleSecurity $security) {}

    public function __invoke(?object $item, array $context): object
    {
        /** @var User $user */
        $user = $this->security->getUser();

        if (!$user instanceof User) {
            throw new \RuntimeException('No authenticated user.');
        }

        return $user;
    }
}
