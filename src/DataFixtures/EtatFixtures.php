<?php

namespace App\DataFixtures;

use App\Entity\Etat;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class EtatFixtures extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $etats = [
            'En création',
            'Ouverte',
            'Clôturée',
            'En cours',
            'Annulée',
            'Terminé'
        ];

        foreach ($etats as $etatName) {
            $etat = new Etat();
            $etat->setLibelle($etatName);
            $manager->persist($etat);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 4;
    }
}
