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
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use App\Entity\User;

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

    public function findLatest(int $page = 1): array {
        $qb = $this->createQueryBuilder('p')
            ->innerJoin('p.author', 'a')
            ->where('p.publishedAt <= :now')
            ->orderBy('p.publishedAt', 'DESC')
            ->setParameter('now', new \DateTime());

        return $this->createPaginator($qb, $page);
    }

    public function findInDateRange(\DateTime $start, \DateTime $end, $visible = false): array {
        $q = $this->createQueryBuilder('p')
            ->where('p.start_date >= :start AND p.start_date < :end')
            ->orWhere('p.end_date > :start AND p.end_date <= :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end);
        if ($visible) {
            $q = $q->andWhere('p.status != 5')
                ->andWhere('p.status != 6');
        }
        return $q->getQuery()
            ->getResult();
    }

    public function findUpcomingAndLastYear($allVisible = true): array {
        $yearAgo = new \DateTime();
        $yearAgo->modify('-1 year');
        $query = $this->createQueryBuilder('p')
            ->where('p.start_date >= :start')
            ->setParameter('start', $yearAgo);

        if (!$allVisible) {
            $query = $query
                ->andWhere('p.status != 5')
                ->andWhere('p.status != 6');
        }
        $query->orderBy('p.publishedAt', 'ASC');
        return $query->getQuery()->getResult();
    }

    private function createPaginator(QueryBuilder $queryBuilder, int $currentPage, int $pageSize = LeaseRequest::NUM_ITEMS) {
        $currentPage = $currentPage < 1 ? 1 : $currentPage;
        $firstResult = ($currentPage - 1) * $pageSize;
        $query = $queryBuilder
            ->setFirstResult($firstResult)
            ->setMaxResults($pageSize)
            ->getQuery();
        $paginator = new Paginator($query);
        $numResults = $paginator->count();
        $hasPreviousPage = $currentPage > 1;
        $hasNextPage = ($currentPage * $pageSize) < $numResults;
        return [
            'results' => $paginator->getIterator(),
            'currentPage' => $currentPage,
            'hasPreviousPage' => $hasPreviousPage,
            'hasNextPage' => $hasNextPage,
            'previousPage' => $hasPreviousPage ? $currentPage - 1 : null,
            'nextPage' => $hasNextPage ? $currentPage + 1 : null,
            'numPages' => (int) ceil($numResults / $pageSize),
            'haveToPaginate' => $numResults > $pageSize,
        ];
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

    /**
     * Finds all requests which have been denied due to occupancy in daterange..
     */
    public function findOccupiedByDateRange(\DateTime $start_date, \DateTime $end_date): array {
        return $this->createQueryBuilder('r')
            ->where('r.start_date BETWEEN :start AND :end')
            ->orWhere('r.start_date BETWEEN :start AND :end')
            ->orWhere('r.start_date < :start AND r.end_date > :end')
            ->orWhere('r.start_date > :start AND r.end_date < :end')
            ->andWhere('r.status = 5 OR r.status = 6' )
            ->orderBy('r.publishedAt')
            ->setParameter('start', $start_date)
            ->setParameter('end', $end_date)
            ->getQuery()
            ->getResult();
    }
}
