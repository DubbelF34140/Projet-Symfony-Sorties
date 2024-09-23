<?php

namespace App\DataFixtures;

use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class VilleFixtures extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $villes = [
            ['nom' => 'Paris', 'codePostal' => '75000'],
            ['nom' => 'Lyon', 'codePostal' => '69000'],
            ['nom' => 'Marseille', 'codePostal' => '13000'],
        ];

        foreach ($villes as $villeData) {
            $ville = new Ville();
            $ville->setNom($villeData['nom']);
            $ville->setCodePostal($villeData['codePostal']);
            $manager->persist($ville);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 2;
    }
}
