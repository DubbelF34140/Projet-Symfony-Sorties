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

            // Vérification pour l'état "Clôturée"
            $etatCloturee = $etatRepository->find($etatRepository->findEtatFinish()); // Assure-toi que l'ID de l'état "Clôturée" est correct
            if ($etatCloturee === null) {
                $logger->error('État "Clôturée" non trouvé dans la base de données.');
                throw new \Exception('État "Clôturée" non trouvé.');
            }

            // Clôturer la sortie si le nombre de participants maximum est atteint ou si la date limite d'inscription est dépassée
            if ($participants >= $maxParticipants || $now > $inscriptionDeadline) {
                $sortie->setEtat($etatCloturee);
                $this->entityManager->persist($sortie);
            }

            // Vérification pour l'état "Historisé"
            $etatHistorise = $etatRepository->find($etatRepository->findEtatHistorise()); // Assure-toi que l'ID de l'état "Historisé" est correct
            if ($etatHistorise === null) {
                $logger->error('État "Historisé" non trouvé dans la base de données.');
                throw new \Exception('État "Historisé" non trouvé.');
            }

            // Historiser la sortie si elle a été réalisée depuis plus d'un mois
            if ($now->diff($dateHeureDebut)->m >= 1 && $now > $dateHeureDebut) {
                $sortie->setEtat($etatHistorise);
                $this->entityManager->persist($sortie);
            }
        }

        $this->entityManager->flush();
    }
}
