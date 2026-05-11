<?php

namespace App\Repository;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Publication;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Publication>
 */
class PublicationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Publication::class);
    }

    /**
     * Publications supprimées (soft delete) pour un auteur.
     * Désactive temporairement le filtre SoftDeleteable.
     *
     * @return Publication[]
     */
    public function findDeletedByAuthor(User $author): array
    {
        $em = $this->getEntityManager();
        $em->getFilters()->disable('softdeleteable');

        $result = $this->createQueryBuilder('p')
            ->andWhere('p.auteur = :author')
            ->andWhere('p.deletedAt IS NOT NULL')
            ->setParameter('author', $author)
            ->orderBy('p.deletedAt', 'DESC')
            ->getQuery()
            ->getResult();

        $em->getFilters()->enable('softdeleteable');

        return $result;
    }

    //    /**
    //     * @return Publication[] Returns an array of Publication objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Publication
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    
}
