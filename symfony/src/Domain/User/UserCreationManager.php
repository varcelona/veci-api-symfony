<?php

namespace App\Domain\User;

use App\Entity\User;
use App\Entity\Customer;
use App\Entity\Merchant;
use Doctrine\ORM\EntityManagerInterface;

class UserCreationManager
{
    public function __construct(private EntityManagerInterface $em) {}

    public function handleUserRoleSideEffects(User $user): void
    {
        $roles = $user->getRoles();

        // ADMIN → no crear ninguna entidad asociada
        if (in_array('ROLE_ADMIN', $roles, true)) {
            return;
        }

        // MERCHANT
        if (in_array('ROLE_MERCHANT', $roles, true)) {
            if (!$user->getMerchant()) {
                $merchant = new Merchant();
                $merchant->setUser($user);

                $this->em->persist($merchant);
            }

            return;
        }

        // CUSTOMER
        if (in_array('ROLE_CUSTOMER', $roles, true)) {
            if (!$user->getCustomer()) {
                $customer = new Customer();
                $customer->setUser($user);

                $this->em->persist($customer);
            }

            return;
        }
    }
}
