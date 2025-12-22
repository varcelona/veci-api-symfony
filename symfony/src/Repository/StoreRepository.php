<?php

namespace App\Repository;

use App\Entity\Store;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;

/**
 * @extends BackendRepository<Store>
 */
class StoreRepository extends BackendRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Store::class);
    }

    public function findByString(?string $string): QueryBuilder
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.brand', 'b')
            ->addSelect('b');

        if ($string) {
            $qb
                ->orWhere('b.title LIKE :title')
                ->setParameter('title', '%'.$string.'%')
                ->orWhere('s.name LIKE :name')
                ->setParameter('name', '%'.$string.'%')
                ->orWhere('s.email LIKE :email')
                ->setParameter('email', '%'.$string.'%')
            ;
        }

        $qb
            ->addOrderBy('s.enabled', 'DESC');

        return $qb;
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
