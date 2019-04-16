<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Repository;

use App\Entity\LeaseRequest;
use App\Entity\Tag;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Common\Persistence\ManagerRegistry;
use Doctrine\ORM\Query;
use Pagerfanta\Adapter\DoctrineORMAdapter;
use App\Entity\User;
use Pagerfanta\Pagerfanta;
use Doctrine\ORM\EntityRepository;

/**
 * This custom Doctrine repository contains some methods which are useful when
 * querying for lease request information.
 *
 * See https://symfony.com/doc/current/doctrine/repository.html
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class LeaseRequestRepository extends ServiceEntityRepository {
    public function __construct(ManagerRegistry $registry) {
        parent::__construct($registry, LeaseRequest::class);
    }

    public function findLatest(int $page = 1): Pagerfanta {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('a', 't')
            ->innerJoin('p.author', 'a')
            ->leftJoin('p.tags', 't')
            ->where('p.publishedAt <= :now')
            ->orderBy('p.publishedAt', 'DESC')
            ->setParameter('now', new \DateTime());

        return $this->createPaginator($qb->getQuery(), $page);
    }

    public function findInDateRange(\DateTime $start, \DateTime $end): array {
        return $this->createQueryBuilder('p')
            ->where('p.start_date >= :start AND p.start_date < :end')
            ->orWhere('p.end_date > :start AND p.end_date <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    public function findUpcomingAndLastYear(): array {
        $yearAgo = new \DateTime();
        $yearAgo->modify('-1 year');
        $query = $this->createQueryBuilder('p')
            ->where('p.start_date >= :start')
            ->setParameter('start', $yearAgo);
        $query->orderBy('p.publishedAt', 'ASC');
        return $query->getQuery()->getResult();
    }

    private function createPaginator(Query $query, int $page): Pagerfanta {
        $paginator = new Pagerfanta(new DoctrineORMAdapter($query));
        $paginator->setMaxPerPage(LeaseRequest::NUM_ITEMS);
        $paginator->setCurrentPage($page);

        return $paginator;
    }

    /**
     * @return LeaseRequest[]
     */
    public function findBySearchQuery(string $rawQuery, int $limit = LeaseRequest::NUM_ITEMS): array {
        $query = $this->sanitizeSearchQuery($rawQuery);
        $searchTerms = $this->extractSearchTerms($query);

        if (0 === \count($searchTerms)) {
            return [];
        }

        $queryBuilder = $this->createQueryBuilder('p');

        foreach ($searchTerms as $key => $term) {
            $queryBuilder
                ->orWhere('p.title LIKE :t_' . $key)
                ->setParameter('t_' . $key, '%' . $term . '%')
            ;
        }

        return $queryBuilder
            ->orderBy('p.publishedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    public function findByAuthor(User $user) {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('p')
            ->where('p.author <= :user')
            ->setParameter('user', $user);
        return $qb->getQuery()->getResult();
    }

    /**
     * Removes all non-alphanumeric characters except whitespaces.
     */
    private function sanitizeSearchQuery(string $query): string {
        return trim(preg_replace('/[[:space:]]+/', ' ', $query));
    }

    /**
     * Splits the search query into terms and removes the ones which are irrelevant.
     */
    private function extractSearchTerms(string $searchQuery): array {
        $terms = array_unique(explode(' ', $searchQuery));

        return array_filter($terms, function ($term) {
            return 2 <= mb_strlen($term);
        });
    }
}
