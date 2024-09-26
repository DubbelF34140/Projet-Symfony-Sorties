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
    public function removeParticipantAndUpdateSorties(Participant $participant, EntityManagerInterface $entityManager, SortieRepository $sortieRepository, EtatRepository $etatRepository): void
    {
        // Désinscrire le participant de toutes les sorties auxquelles il est inscrit
        $sortiesInscrit = $sortieRepository->getSortiesInscrit($participant);
        foreach ($sortiesInscrit as $sortie) {
            $sortie->removeInscrit($participant);
            $entityManager->persist($sortie);
        }

        // Annuler toutes les sorties dont le participant est l'organisateur
        $sortiesOrganisees = $sortieRepository->getSortiesOrganisees($participant);
        foreach ($sortiesOrganisees as $sortie) {
            // Passer à l'état "Annulé"
            $etatAnnule = $etatRepository->findEtatAnnulee();
            $sortie->setEtat($etatAnnule);
            $sortie->setInfosuppr('Annulé car l\'organisateur a été supprimé.');
            $entityManager->persist($sortie);
        }

        // Après avoir annulé ou mis à jour les sorties, supprimer le participant
        $entityManager->remove($participant);
        $entityManager->flush();
    }


}
