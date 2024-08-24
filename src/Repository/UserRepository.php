<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    //    /**
    //     * @return User[] Returns an array of User objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('u')
    //            ->andWhere('u.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('u.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }
    public function findTopUsersByWins(int $limit = 10): array
    {
        return $this->createQueryBuilder('u')
            ->orderBy('u.wins', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
    public function searchUsers(string $term): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.username LIKE :term')
            ->setParameter('term', '%' . $term . '%')
            ->getQuery()
            ->getResult();
    }
    public function findOneBySomeField($value): ?User
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.username = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }

    // src/Repository/UserRepository.php

    public function findAcceptedRequestsForUser(User $user): array
    {
        $qb = $this->createQueryBuilder('u');
        $qb->select('r')
            ->join('u.requests', 'r')
            ->where('r.accepted = true')
            ->andWhere('r.receiver = :user OR r.sender = :user')
            ->setParameter('user', $user);

        return $qb->getQuery()->getResult();
    }
}
