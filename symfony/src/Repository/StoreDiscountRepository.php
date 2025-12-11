<?php

namespace App\Repository;

use App\Entity\Store;
use App\Entity\StoreDiscount;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class StoreDiscountRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, StoreDiscount::class);
    }

    /**
     * Obtener descuentos activos (vigentes según fechas)
     *
     * @return StoreDiscount[]
     */
    public function findActiveDiscounts(): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('d')
            ->andWhere('d.validFrom <= :now')
            ->andWhere('d.validTo >= :now')
            ->setParameter('now', $now)
            ->orderBy('d.validFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtener descuentos activos para un store específico
     *
     * @param Store|int $store Store o storeId
     * @return StoreDiscount[]
     */
    public function findActiveByStore($store): array
    {
        $now = new \DateTimeImmutable();

        return $this->createQueryBuilder('d')
            ->andWhere('d.store = :store')
            ->andWhere('d.validFrom <= :now')
            ->andWhere('d.validTo >= :now')
            ->setParameter('store', $store instanceof Store ? $store->getId() : $store)
            ->setParameter('now', $now)
            ->orderBy('d.validFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtener descuentos por store sin validación temporal.
     *
     * @return StoreDiscount[]
     */
    public function findByStore($storeId): array
    {
        return $this->createQueryBuilder('d')
            ->andWhere('d.store = :store')
            ->setParameter('store', $storeId)
            ->orderBy('d.validFrom', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Guarda una entidad.
     */
    public function save(StoreDiscount $discount, bool $flush = true): void
    {
        $this->_em->persist($discount);

        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Elimina un descuento.
     */
    public function remove(StoreDiscount $discount, bool $flush = true): void
    {
        $this->_em->remove($discount);

        if ($flush) {
            $this->_em->flush();
        }
    }
}
