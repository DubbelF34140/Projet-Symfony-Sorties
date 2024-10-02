<?php

namespace App\Repository;

use App\Entity\Participant;
use App\Entity\Sortie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Sortie>
 */
class SortieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Sortie::class);
    }

    public function searchSorties(Participant $user, EtatRepository $etatRepository, array $filters = [])
    {
        $qb = $this->createQueryBuilder('s')
            ->leftJoin('s.organisateur', 'o')
            ->addSelect('o')
            ->leftJoin('s.campus', 'c')
            ->addSelect('c')
            ->leftJoin('s.inscrits', 'i')
            ->addSelect('i');

        if (!empty($filters['campus'])) {
            $qb->andWhere('s.campus = :campus')
                ->setParameter('campus', $filters['campus']);
        }

        if (!empty($filters['nom'])) {
            $qb->andWhere('s.nom LIKE :nom')
                ->setParameter('nom', '%' . $filters['nom'] . '%');
        }

        if (!empty($filters['dateDebut'])) {
            $qb->andWhere('s.dateHeureDebut >= :dateDebut')
                ->setParameter('dateDebut', $filters['dateDebut']);
        }

        if (!empty($filters['dateFin'])) {
            $qb->andWhere('s.dateHeureDebut <= :dateFin')
                ->setParameter('dateFin', $filters['dateFin']);
        }

        if (!empty($filters['organisateur'])) {
            $qb->andWhere('s.organisateur = :organisateur')
                ->setParameter('organisateur', $filters['organisateur']);
        }

        if (!empty($filters['inscrit'])) {
            $qb->andWhere(':user MEMBER OF s.inscrits')
                ->setParameter('user', $filters['inscrit']);
        }

        if (!empty($filters['nonInscrit'])) {
            $qb->andWhere(':user NOT MEMBER OF s.inscrits')
                ->setParameter('user', $filters['nonInscrit']);
        }

        if (!empty($filters['terminees'])) {
            $cloture = $etatRepository->findEtatFinish();

            if ($cloture) {
                $qb->andWhere('s.etat = :terminee')
                    ->setParameter('terminee', $cloture);
            }
        }

        if (!$user->isAdministrateur()) {
            $enCreation = $etatRepository->findEtatCreation();
            if ($enCreation) {
                $qb->andWhere('s.etat != :enCreation OR s.organisateur = :user')
                    ->setParameter('enCreation', $enCreation)
                    ->setParameter('user', $user);
            }
        }

        $historique = $etatRepository->findEtatHistorise();

        $qb->andWhere('s.etat != :historique')
            ->setParameter('historique', $historique);

        $qb->orderBy('s.dateHeureDebut', 'ASC');

        return $qb->getQuery()->getResult();
    }

    //    /**
    //     * @return Sortie[] Returns an array of Sortie objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('s.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Sortie
    //    {
    //        return $this->createQueryBuilder('s')
    //            ->andWhere('s.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    /**
     * @throws \Exception
     */
    public function findByFilters(EtatRepository $etatRepository, ?string $etat = null, ?\DateTimeInterface $date = null)
    {
        $qb = $this->createQueryBuilder('s')
            ->join('s.etat', 'e') // Associe l'état à la sortie
            ->where('e.libelle != :etatCreation')
            ->andWhere('e.libelle != :etatTermine')
            ->setParameter('etatCreation', $etatRepository->findEtatCreation()->getLibelle())
            ->setParameter('etatTermine', $etatRepository->findEtatFinish()->getLibelle());

        if ($etat) {
            $qb->andWhere('e.libelle = :etat')
                ->setParameter('etat', $etat);
        }

        if ($date) {
            $qb->andWhere('s.dateHeureDebut >= :date')
                ->setParameter('date', $date);
        }

        return $qb->getQuery()->getResult();
    }


    public function getSortiesInscrit(Participant $participant)
    {
        return $this->createQueryBuilder('s')
            ->innerJoin('s.inscrits', 'p')
            ->innerJoin('s.etat', 'e')
            ->where('p.id = :participantId')
            ->andWhere('e.libelle = :etatOuvert')
            ->setParameter('participantId', $participant->getId())
            ->setParameter('etatOuvert', 'Ouverte')
            ->getQuery()
            ->getResult();
    }


    public function findDetailSortie(int $id)
    {
        return $this->createQueryBuilder('s')
            ->leftJoin('s.organisateur', 'o') // Fetch join pour l'organisateur
            ->addSelect('o')
            ->leftJoin('s.campus', 'c') // Fetch join pour le campus
            ->addSelect('c')
            ->leftJoin('s.inscrits', 'i') // Fetch join pour les inscrits
            ->addSelect('i')
            ->leftJoin('s.PrivateParticipants', 'p') // Fetch join pour les inscrits
            ->addSelect('p')
            ->leftJoin('s.lieu', 'l') // Fetch join pour le lieu
            ->addSelect('l')
            ->leftJoin('l.ville', 'v') // Fetch join pour la ville du lieu
            ->addSelect('v')
            ->leftJoin('s.etat', 'e') // Fetch join pour l'état
            ->addSelect('e')
            ->where('s.id = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }


}
