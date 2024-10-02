<?php

namespace App\Repository;

use App\Entity\Groupes;
use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Groupes>
 */
class GroupsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Groupes::class);
    }

    /**
     * Récupère tous les groupes dont l'utilisateur spécifié est le propriétaire (owner)
     *
     * @param Participant $user
     * @return Groupes[]
     */
    public function findByOwner(Participant $user): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.owner = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getResult();
    }


//    /**
//     * @return Groupes[] Returns an array of Groupes objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('g.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Groupes
//    {
//        return $this->createQueryBuilder('g')
//            ->andWhere('g.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
    public function searchByNameAndOwner(string $searchTerm, Participant $owner): array
    {
        return $this->createQueryBuilder('g')
            ->where('g.nom LIKE :searchTerm')
            ->andWhere('g.owner = :owner')
            ->setParameter('searchTerm', '%' . $searchTerm . '%')
            ->setParameter('owner', $owner)
            ->getQuery()
            ->getResult();
    }

}
