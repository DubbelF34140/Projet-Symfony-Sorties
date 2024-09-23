<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;


class CampusFixtures extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $campusList = [
            'Campus Paris',
            'Campus Lyon',
            'Campus Marseille',
            'Campus Toulouse',
            'Campus Bordeaux'
        ];

        foreach ($campusList as $campusName) {
            $campus = new Campus();
            $campus->setNom($campusName);
            $manager->persist($campus);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 5;
    }
}
