<?php

namespace App\Repository;

use App\Entity\StoreSchedule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends BackendRepository<StoreSchedule>
 */
class StoreScheduleRepository extends BackendRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreSchedule::class);
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
