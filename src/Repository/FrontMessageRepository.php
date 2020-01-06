<?php

namespace App\Repository;

use App\Entity\FrontMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;

/**
 * @method FrontMessage|null find($id, $lockMode = null, $lockVersion = null)
 * @method FrontMessage|null findOneBy(array $criteria, array $orderBy = null)
 * @method FrontMessage[]    findAll()
 * @method FrontMessage[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class FrontMessageRepository extends ServiceEntityRepository {

    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, FrontMessage::class);
    }

    /**
     * @param $date
     * @return Array
     */
    public function findOneByDateTime($date): Array{
        return $this->createQueryBuilder('f')
            ->where('f.start_date < :date')
            ->andWhere('f.end_date > :date')
            ->setParameter('date', $date)
            ->getQuery()
            ->getResult()
        ;
    }
}
