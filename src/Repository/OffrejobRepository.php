<?php

namespace App\Repository;

use App\Entity\Offrejob;
use App\Enum\ModerationStatus;
use App\Enum\OffreStatut;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Offrejob>
 */
class OffrejobRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Offrejob::class);
    }

    /** Front: offres visibles (recherche + filtres + tri) */
    /**
     * @return Offrejob[]
     */
    public function searchAndSort(
        ?string $q,
        ?string $categorie,
        ?string $lieu,
        ?string $statut,
        string $sort = 'date',
        string $dir = 'desc',
        bool $approvedOnly = false
    ): array
    {
        return $this->searchAndSortQueryBuilder($q, $categorie, $lieu, $statut, $sort, $dir, $approvedOnly)
            ->getQuery()
            ->getResult();
    }

    /** Front: same filters/sort, but query builder for pagination */
    public function searchAndSortQueryBuilder(
        ?string $q,
        ?string $categorie,
        ?string $lieu,
        ?string $statut,
        string $sort = 'date',
        string $dir = 'desc',
        bool $approvedOnly = false
    ): QueryBuilder {
        $qb = $this->createQueryBuilder('o');

        if ($approvedOnly) {
            $qb->andWhere('o.moderationStatus = :moderation_approved')
               ->setParameter('moderation_approved', ModerationStatus::APPROVED);
        }

        if ($q) {
            $qb->andWhere('LOWER(o.titre_offre) LIKE :q
                        OR LOWER(o.description_offre) LIKE :q
                        OR LOWER(o.categorie_offre) LIKE :q
                        OR LOWER(o.lieu) LIKE :q
                        OR LOWER(COALESCE(o.adresse, \'\')) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $this->applyFilters($qb, $categorie, $lieu, $statut);

        $map = [
            'date' => 'o.date_creation_offre',
            'titre' => 'o.titre_offre',
            'categorie' => 'o.categorie_offre',
            'lieu' => 'o.lieu',
            'statut' => 'o.statut_offre',
        ];

        $sortCol = $map[$sort] ?? $map['date'];
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        return $qb->orderBy($sortCol, $dir);
    }

    /** Créateur: mes offres (recherche + filtres + tri) */
    /**
     * @return Offrejob[]
     */
    public function searchMine(
        int $creatorId,
        ?string $q,
        ?string $categorie,
        ?string $lieu,
        ?string $statut,
        string $sort = 'date',
        string $dir = 'desc'
    ): array
    {
        $qb = $this->createQueryBuilder('o')
            ->andWhere('o.createur = :cid')
            ->setParameter('cid', $creatorId);

        if ($q) {
            $qb->andWhere('LOWER(o.titre_offre) LIKE :q
                        OR LOWER(o.description_offre) LIKE :q
                        OR LOWER(o.categorie_offre) LIKE :q
                        OR LOWER(o.lieu) LIKE :q
                        OR LOWER(COALESCE(o.adresse, \'\')) LIKE :q')
               ->setParameter('q', '%'.mb_strtolower($q).'%');
        }

        $this->applyFilters($qb, $categorie, $lieu, $statut);

        $map = [
            'date' => 'o.date_creation_offre',
            'titre' => 'o.titre_offre',
            'categorie' => 'o.categorie_offre',
            'lieu' => 'o.lieu',
            'statut' => 'o.statut_offre',
        ];

        $sortCol = $map[$sort] ?? $map['date'];
        $dir = strtolower($dir) === 'asc' ? 'ASC' : 'DESC';

        return $qb->orderBy($sortCol, $dir)->getQuery()->getResult();
    }

    /**
     * @return Offrejob[]
     */
    public function findExpiredOpenedOffers(\DateTimeImmutable $now): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.statut_offre = :opened')
            ->andWhere('o.date_expiration < :now')
            ->setParameter('opened', OffreStatut::OUVERTE->value)
            ->setParameter('now', $now)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return Offrejob[]
     */
    public function findPendingModeration(): array
    {
        return $this->createQueryBuilder('o')
            ->andWhere('o.moderationStatus = :pending')
            ->setParameter('pending', ModerationStatus::PENDING)
            ->orderBy('o.date_creation_offre', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function applyFilters(QueryBuilder $qb, ?string $categorie, ?string $lieu, ?string $statut): void
    {
        if ($categorie) {
            $qb->andWhere('o.categorie_offre = :categorie')
               ->setParameter('categorie', $categorie);
        }

        if ($lieu) {
            $qb->andWhere('o.lieu = :lieu')
               ->setParameter('lieu', $lieu);
        }

        if ($statut) {
            $now = new \DateTimeImmutable();

            if ($statut === OffreStatut::OUVERTE->value) {
                $qb->andWhere('o.statut_offre = :statut_open')
                   ->andWhere('o.date_expiration >= :now')
                   ->setParameter('statut_open', OffreStatut::OUVERTE->value)
                   ->setParameter('now', $now);
            } elseif ($statut === OffreStatut::EXPIREE->value) {
                $qb->andWhere('(o.statut_offre = :statut_expiree OR (o.statut_offre = :statut_opened AND o.date_expiration < :now))')
                   ->setParameter('statut_expiree', OffreStatut::EXPIREE->value)
                   ->setParameter('statut_opened', OffreStatut::OUVERTE->value)
                   ->setParameter('now', $now);
            } else {
                $qb->andWhere('o.statut_offre = :statut')
                   ->setParameter('statut', $statut);
            }
        }
    }
}
