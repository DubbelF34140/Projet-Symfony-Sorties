<?php

namespace App\Service;

use App\Entity\Sortie;
use App\Repository\EtatRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

class SortieStatusService
{
    private $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    public function checkSortieStatus(LoggerInterface $logger, EtatRepository $etatRepository): void
    {
        $logger->info('Checking sortie statuses');
        $sorties = $this->entityManager->getRepository(Sortie::class)->findAll();

        foreach ($sorties as $sortie) {
            $now = new \DateTime();
            $inscriptionDeadline = $sortie->getDateLimiteInscription();
            $maxParticipants = $sortie->getNbInscriptionMax();
            $participants = $sortie->getInscrits()->count();
            $dateHeureDebut = $sortie->getDateHeureDebut();

            $etatCloturee = $etatRepository->find($etatRepository->findEtatFinish());
            if ($etatCloturee === null) {
                $logger->error('État "Clôturée" non trouvé dans la base de données.');
                throw new \Exception('État "Clôturée" non trouvé.');
            }

            if ($participants >= $maxParticipants || $now > $inscriptionDeadline) {
                $sortie->setEtat($etatCloturee);
                $this->entityManager->persist($sortie);
            }

            $etatHistorise = $etatRepository->find($etatRepository->findEtatHistorise());
            if ($etatHistorise === null) {
                $logger->error('État "Historisé" non trouvé dans la base de données.');
                throw new \Exception('État "Historisé" non trouvé.');
            }

            if ($now->diff($dateHeureDebut)->m >= 1 && $now > $dateHeureDebut) {
                $sortie->setEtat($etatHistorise);
                $this->entityManager->persist($sortie);
            }
        }

        $this->entityManager->flush();
    }
}
