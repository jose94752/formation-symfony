<?php

namespace App\Repository;

use App\DTO\OrderSearch;
use App\Entity\Order;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Order|null find($id, $lockMode = null, $lockVersion = null)
 * @method Order|null findOneBy(array $criteria, array $orderBy = null)
 * @method Order[]    findAll()
 * @method Order[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OrderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Order::class);
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function add(Order $entity, bool $flush = true): void
    {
        $this->_em->persist($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function remove(Order $entity, bool $flush = true): void
    {
        $this->_em->remove($entity);
        if ($flush) {
            $this->_em->flush();
        }
    }

    /**
     * Récupére toutes les commandes passé d'un utilisateur
     * trié par date de création
     */
    public function findAllForUser(User $user): array
    {
        return $this
            ->createQueryBuilder('o')
            ->orderBy('o.createdAt', 'DESC')
            ->leftJoin('o.user', 'user')
            ->andWhere('user.id = :id')
            ->setParameter('id', $user->getId())
            ->getQuery()
            ->getResult();
    }

    /**
     * Récupére toutes les commandes filtré par le DTO
     * OrderSearch
     */
    public function findAllBySearch(OrderSearch $search): array
    {
        $qb = $this
            ->createQueryBuilder('o')
            ->setMaxResults($search->limit)
            ->setFirstResult($search->limit * ($search->page - 1))
            ->orderBy("o.{$search->sortBy}", $search->direction);

        if ($search->statuses) {
            $qb
                ->andWhere('o.status IN (:statuses)')
                ->setParameter('statuses', $search->statuses);
        }

        if ($search->user) {
            $qb
                ->leftJoin('o.user', 'u')
                ->andWhere('u.id = :userId')
                ->setParameter('userId', $search->user->getId());
        }

        return $qb->getQuery()->getResult();
    }
}
