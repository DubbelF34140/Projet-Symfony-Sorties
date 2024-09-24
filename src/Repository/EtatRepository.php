<?php

namespace App\Repository;

use App\Entity\Etat;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Etat>
 */
class EtatRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Etat::class);
    }

    /**
     * Retourne l'état "En création"
     */
    public function findEtatCreation(): ?Etat
    {
        return $this->findOneBy(['libelle' => 'En création']);
    }
    public function findEtatHistorise(): ?Etat
    {
        return $this->findOneBy(['libelle' => 'Historisée']);
    }

    public function findEtatFinish(): ?Etat
    {
        return $this->findOneBy(['libelle' => 'Clôturée']);
    }

    /**
     * Retourne l'état "Publiée"
     */
    public function findEtatPubliee(): ?Etat
    {
        return $this->findOneBy(['libelle' => 'Ouverte']);
    }
    /**
     * Retourne l'état "Annulée"
     */
    public function findEtatAnnulee(): ?Etat
    {
        return $this->findOneBy(['libelle' => 'Annulée']);
    }
}
