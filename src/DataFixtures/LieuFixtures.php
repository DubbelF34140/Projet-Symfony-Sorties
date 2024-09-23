<?php

namespace App\DataFixtures;

use App\Entity\Lieu;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class LieuFixtures extends Fixture implements OrderedFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        // Assurez-vous que les villes existent en base
        $paris = $manager->getRepository(Ville::class)->findOneBy(['nom' => 'Paris']);
        $lyon = $manager->getRepository(Ville::class)->findOneBy(['nom' => 'Lyon']);
        $marseille = $manager->getRepository(Ville::class)->findOneBy(['nom' => 'Marseille']);

        if (!$paris || !$lyon || !$marseille) {
            throw new \Exception('Les villes doivent exister avant de créer les lieux');
        }

        // Liste des lieux (sans code postal, car il est dans Ville)
        $lieux = [
            ['nom' => 'Parc de la Tête d\'Or', 'rue' => 'Boulevard des Belges', 'latitude' => 45.7831, 'longitude' => 4.8657, 'ville' => $lyon],
            ['nom' => 'Place Bellecour', 'rue' => 'Place Bellecour', 'latitude' => 45.7578, 'longitude' => 4.8328, 'ville' => $lyon],
            ['nom' => 'Jardin des Plantes', 'rue' => '57 Rue Cuvier', 'latitude' => 48.8439, 'longitude' => 2.3562, 'ville' => $paris],
            ['nom' => 'Vieux Port', 'rue' => 'Quai du Port', 'latitude' => 43.2965, 'longitude' => 5.3698, 'ville' => $marseille],
        ];

        foreach ($lieux as $lieuData) {
            $lieu = new Lieu();
            $lieu->setNom($lieuData['nom']);
            $lieu->setRue($lieuData['rue']);
            $lieu->setLatitude($lieuData['latitude']);
            $lieu->setLongitude($lieuData['longitude']);
            $lieu->setVille($lieuData['ville']); // Lien avec la ville
            $manager->persist($lieu);
        }

        $manager->flush();
    }

    public function getOrder(): int
    {
        return 3;
    }
}
