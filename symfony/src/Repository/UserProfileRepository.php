<?php

namespace App\Repository;

use App\Entity\UserProfile;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BackendRepository<UserProfile>
 *
 * @method UserProfile|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserProfile|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserProfile[]    findAll()
 * @method UserProfile[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserProfileRepository extends BackendRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProfile::class);
    }

    public function save($entity, bool $flush = false): void
    {
        parent::save($entity, $flush);
    }

    public function remove($entity): void
    {
        parent::remove($entity);
    }

}
