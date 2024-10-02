<?php

namespace App\Repository;

use App\Entity\Participant;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<Participant>
 */
class ParticipantRepository extends ServiceEntityRepository implements PasswordUpgraderInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participant::class);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof Participant) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    //    /**
    //     * @return Participant[] Returns an array of Participant objects
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

    //    public function findOneBySomeField($value): ?Participant
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
    public function removeParticipantIfNoSorties(
        Participant $participant,
        EntityManagerInterface $entityManager,
        SortieRepository $sortieRepository
    ): void {
        $sortiesInscritOuvertes = $sortieRepository->getSortiesInscrit($participant);

        if (!empty($sortiesInscritOuvertes)) {
            foreach ($sortiesInscritOuvertes as $sortie) {
                $sortie->removeInscrit($participant);
            }
        }

        $autresSortiesInscrit = $sortieRepository->getSortiesInscrit($participant);

        if (empty($autresSortiesInscrit)) {
            $entityManager->remove($participant);
            $entityManager->flush();
        } else {
            throw new \Exception("Le participant est encore inscrit à des sorties ouvertes.");
        }
    }

    public function searchParticipants(string $query): array
    {
        return $this->createQueryBuilder('p')
            ->where('p.nom LIKE :query OR p.pseudo LIKE :query')
            ->setParameter('query', '%' . $query . '%')
            ->setMaxResults(10)  // Limite les résultats pour éviter une surcharge
            ->getQuery()
            ->getResult();
    }


}
