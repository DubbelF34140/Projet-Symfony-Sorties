<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use DateInterval;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\ORM\EntityManager;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use PhpParser\Node\Scalar\String_;


class SortieFixture extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Récupérer les lieux disponibles
        $lieux = $manager->getRepository(Lieu::class)->findAll();

        // Récupérer les états (ex : "En création")
        $etat = $manager->getRepository(Etat::class)->findOneBy(['libelle' => 'Ouverte']);

        // Récupérer les campus disponibles
        $campus = $manager->getRepository(Campus::class)->findAll();

        // Récupérer les participants disponibles
        $participants = $manager->getRepository(Participant::class)->findAll();

        // Générer 24 sorties
        for ($i = 1; $i <= 24; $i++) {
            // Sélectionner aléatoirement un lieu, un campus, et un organisateur
            $randomLieu = $lieux[array_rand($lieux)];
            $randomCampus = $campus[array_rand($campus)];
            $randomOrganisateur = $participants[array_rand($participants)];

            // Créer une liste aléatoire de participants inscrits
            $randomParticipants = [];
            $numberOfParticipants = rand(1, min(10, count($participants))); // Limite à 10 max ou moins si pas assez de participants
            $selectedParticipants = array_rand($participants, $numberOfParticipants);

            // Gérer le cas où il y a seulement un participant sélectionné
            if (is_array($selectedParticipants)) {
                foreach ($selectedParticipants as $key) {
                    $randomParticipants[] = $participants[$key];
                }
            } else {
                $randomParticipants[] = $participants[$selectedParticipants];
            }

            // Générer des données aléatoires pour chaque sortie
            $nomSortie = "Sortie Aléatoire {$i}";
            $nbInscriptionMax = rand(10, 50);
            $duree = rand(60, 180);
            $infos = "Ceci est une sortie aléatoire n°{$i} avec des informations variées.";

            // Utiliser la méthode `create` pour créer une sortie
            $this->create(
                $randomLieu,
                $etat,
                $nomSortie,
                $nbInscriptionMax,
                $duree,
                $infos,
                $randomOrganisateur,
                $randomCampus,
                $randomParticipants,
                $manager
            );
        }

        $manager->flush();
    }
    public function create(Lieu $Parc, Etat $state, String $Nom, int $numbermax, int $duree, String $information, Participant $orga, Campus $camp, Participant|array $participant = [], EntityManager $manager): int
    {
        $sortie = new Sortie();
        $sortie->setLieu($Parc);
        $sortie->setEtat($state);
        $sortie->setNom($Nom);
        $dateDebut = (new \DateTime('now'))->add(new DateInterval('P4D'));
        $sortie->setDateHeureDebut($dateDebut);
        $dateLim = $dateDebut->sub(new DateInterval('P2D'));
        $sortie->setDateLimiteInscription($dateLim);
        $sortie->setNbInscriptionMax($numbermax);
        $sortie->setDuree($duree);
        $sortie->setInfosSortie($information);
        $sortie->setOrganisateur($orga);
        $sortie->setCampus($camp);
        if (is_array($participant)) {
            foreach ($participant as $part) {
                $sortie->addInscrit($part);
            }
        } else {
            $sortie->addInscrit($participant);
        }
        $manager->persist($sortie);
        $manager->flush();

        return $sortie->getId();
    }


    public function getOrder(): int
    {
        return 7;
    }
}
