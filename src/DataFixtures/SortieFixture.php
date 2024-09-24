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
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;


class SortieFixture extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // $product = new Product();
        // $manager->persist($product);
        //Liste de lieux
        $Parc = $manager->getRepository(Lieu::class)->findOneBy(['nom' => 'Parc de la Tête d\'Or']);
        $Place = $manager->getRepository(Lieu::class)->findOneBy(['nom' => 'Place Bellecour']);
        $Port = $manager->getRepository(Lieu::class)->findOneBy(['nom' => 'Vieux Port']);

        //Liste des états
        $creation = $manager->getRepository(Etat::class)->findOneBy(['libelle' => 'En création']);
        if (!$creation) {
            throw new \Exception('manque état');
        }

        //Liste des campus
        $paris = $manager->getRepository(Campus::class)->findOneBy(['nom' => 'Campus Paris']);
        $lyon = $manager->getRepository(Campus::class)->findOneBy(['nom' => 'Campus Lyon']);
        $mars = $manager->getRepository(Campus::class)->findOneBy(['nom' => 'Campus Marseille']);
        $toul = $manager->getRepository(Campus::class)->findOneBy(['nom' => 'Campus Toulouse']);
        $borde = $manager->getRepository(Campus::class)->findOneBy(['nom' => 'Campus Bordeaux']);


        //Liste des participants
        $ad1 = $manager->getRepository(Participant::class)->findOneBy(['pseudo' => 'AD1']);
        $user1 = $manager->getRepository(Participant::class)->findOneBy(['pseudo' => 'US1']);
        $user2 = $manager->getRepository(Participant::class)->findOneBy(['pseudo' => 'US2']);

        $sortie1 = new Sortie();
        $sortie1->setLieu($Parc);
        $sortie1->setEtat($creation);
        $sortie1->setNom('sortie1');
        $dateDebut = (new \DateTime('now'));
        $dateDebut = $dateDebut->add(new DateInterval('P4D'));;
        $sortie1->setDateHeureDebut($dateDebut);
        $dateLim = $dateDebut->sub(new DateInterval('P2D'));;
        $sortie1->setDateLimiteInscription($dateLim);
        $sortie1->setNbInscriptionMax(40);
        $sortie1->setDuree(160);
        $sortie1-> setInfosSortie('info sortie1');
        $sortie1->setOrganisateur($ad1);
        $sortie1->setCampus($borde);
        $sortie1->addInscrit($user1);
        $sortie1->addInscrit($user2);
        $manager->persist($sortie1);

        $sortie2 = new Sortie();
        $sortie2->setLieu($Port);
        $sortie2->setEtat($creation);
        $sortie2->setNom('sortie2');
        $dateDebut = (new \DateTime('now'));
        $dateDebut = $dateDebut->add(new DateInterval('P10D'));;
        $sortie2->setDateHeureDebut($dateDebut);
        $dateLim = $dateDebut->sub(new DateInterval('P2D'));;
        $sortie2->setDateLimiteInscription($dateLim);
        $sortie2->setNbInscriptionMax(20);
        $sortie2->setDuree(120);
        $sortie2-> setInfosSortie('info sortie2');
        $sortie2->setOrganisateur($user1);
        $sortie2->setCampus($mars);
        $sortie2->addInscrit($user2);
        $manager->persist($sortie2);

        $sortie3 = new Sortie();
        $sortie3->setLieu($Place);
        $sortie3->setEtat($creation);
        $sortie3->setNom('sortie3');
        $dateDebut = (new \DateTime('now'));
        $dateDebut = $dateDebut->add(new DateInterval('P30D'));;
        $sortie3->setDateHeureDebut($dateDebut);
        $dateLim = $dateDebut->sub(new DateInterval('P2D'));;
        $sortie3->setDateLimiteInscription($dateLim);
        $sortie3->setNbInscriptionMax(30);
        $sortie3->setDuree(130);
        $sortie3-> setInfosSortie('info sortie3');
        $sortie3->setOrganisateur($user2);
        $sortie3->setCampus($paris);
        $sortie3->addInscrit($user1);
        $sortie3->addInscrit($ad1);
        $manager->persist($sortie3);

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 7;
    }
}
