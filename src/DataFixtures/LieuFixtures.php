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
        $paris = $manager->getRepository(Ville::class)->findOneBy(['nom' => 'Paris']);
        $lyon = $manager->getRepository(Ville::class)->findOneBy(['nom' => 'Lyon']);
        $marseille = $manager->getRepository(Ville::class)->findOneBy(['nom' => 'Marseille']);

        if (!$paris || !$lyon || !$marseille) {
            throw new \Exception('Les villes doivent exister avant de créer les lieux');
        }

        $lieux = [
            ['nom' => 'Parc de la Tête d\'Or', 'rue' => 'Boulevard des Belges', 'latitude' => 45.7831, 'longitude' => 4.8657, 'ville' => $lyon],
            ['nom' => 'Place Bellecour', 'rue' => 'Place Bellecour', 'latitude' => 45.7578, 'longitude' => 4.8328, 'ville' => $lyon],
            ['nom' => 'Jardin des Plantes', 'rue' => '57 Rue Cuvier', 'latitude' => 48.8439, 'longitude' => 2.3562, 'ville' => $paris],
            ['nom' => 'Vieux Port', 'rue' => 'Quai du Port', 'latitude' => 43.2965, 'longitude' => 5.3698, 'ville' => $marseille],
            ['nom' => 'Basilique Notre-Dame de Fourvière', 'rue' => '8 Place de Fourvière', 'latitude' => 45.7622, 'longitude' => 4.8228, 'ville' => $lyon],
            ['nom' => 'Tour Eiffel', 'rue' => 'Champ de Mars, 5 Avenue Anatole France', 'latitude' => 48.8584, 'longitude' => 2.2945, 'ville' => $paris],
            ['nom' => 'Notre-Dame de Paris', 'rue' => '6 Parvis Notre-Dame - Place Jean-Paul II', 'latitude' => 48.8530, 'longitude' => 2.3499, 'ville' => $paris],
            ['nom' => 'Château d\'If', 'rue' => 'Île d\'If', 'latitude' => 43.2922, 'longitude' => 5.3105, 'ville' => $marseille],
            ['nom' => 'Parc Borély', 'rue' => 'Avenue du Parc Borély', 'latitude' => 43.2615, 'longitude' => 5.3763, 'ville' => $marseille],
            ['nom' => 'Musée du Louvre', 'rue' => 'Rue de Rivoli', 'latitude' => 48.8606, 'longitude' => 2.3376, 'ville' => $paris],
            ['nom' => 'Hôtel de Ville de Lyon', 'rue' => '1 Place de la Comédie', 'latitude' => 45.7673, 'longitude' => 4.8349, 'ville' => $lyon],
            ['nom' => 'La Canebière', 'rue' => 'La Canebière', 'latitude' => 43.2954, 'longitude' => 5.3793, 'ville' => $marseille],
            ['nom' => 'Sacré-Cœur', 'rue' => '35 Rue du Chevalier de la Barre', 'latitude' => 48.8867, 'longitude' => 2.3431, 'ville' => $paris],
            ['nom' => 'Parc Longchamp', 'rue' => 'Boulevard du Jardin Zoologique', 'latitude' => 43.3048, 'longitude' => 5.3915, 'ville' => $marseille],
            ['nom' => 'Opéra National de Lyon', 'rue' => '1 Place de la Comédie', 'latitude' => 45.7671, 'longitude' => 4.8346, 'ville' => $lyon],
            ['nom' => 'Pont Neuf', 'rue' => 'Pont Neuf', 'latitude' => 48.8577, 'longitude' => 2.3412, 'ville' => $paris],
            ['nom' => 'Vieux Lyon', 'rue' => 'Rue Saint-Jean', 'latitude' => 45.7626, 'longitude' => 4.8277, 'ville' => $lyon],
            ['nom' => 'Calanques de Marseille', 'rue' => 'Calanques', 'latitude' => 43.2172, 'longitude' => 5.4484, 'ville' => $marseille],
            ['nom' => 'Montmartre', 'rue' => 'Place du Tertre', 'latitude' => 48.8862, 'longitude' => 2.3406, 'ville' => $paris],
            ['nom' => 'Gare Saint-Charles', 'rue' => 'Square Narvik', 'latitude' => 43.3022, 'longitude' => 5.3809, 'ville' => $marseille],
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
