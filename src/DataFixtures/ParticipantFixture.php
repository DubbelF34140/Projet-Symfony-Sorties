<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Participant;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class ParticipantFixture extends Fixture implements OrderedFixtureInterface
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {

        $paris = $manager->getRepository(Campus::class)->findOneBy(['nom' => 'Campus Paris']);
        $lyon = $manager->getRepository(Campus::class)->findOneBy(['nom' => 'Campus Lyon']);

        // Création de l'administrateur
        $admin = new Participant();
        $admin->setPseudo('AD1')
            ->setEmail('admin@sortir.com')
            ->setNom('AdminNom')
            ->setPrenom('AdminPrenom')
            ->setTelephone('0600000000')
            ->setAdministrateur(true)
            ->setActif(true)
            ->setPassword($this->passwordHasher->hashPassword($admin, 'adminpassword'))
            ->setCampus($paris)
            ->setFirstconnection(false);
        $admin->setRoles(['ROLE_ADMIN']);
        $manager->persist($admin);

        // Création du premier utilisateur
        $user1 = new Participant();
        $user1->setPseudo('US1')
            ->setEmail('user1@sortir.com')
            ->setNom('User1Nom')
            ->setPrenom('User1Prenom')
            ->setTelephone('0611111111')
            ->setAdministrateur(false)
            ->setActif(true)
            ->setPassword($this->passwordHasher->hashPassword($user1, 'user1password'))
            ->setCampus($paris)
            ->setFirstconnection(false);
        $manager->persist($user1);

        // Création du second utilisateur
        $user2 = new Participant();
        $user2->setPseudo('US2')
            ->setEmail('user2@sortir.com')
            ->setNom('User2Nom')
            ->setPrenom('User2Prenom')
            ->setTelephone('0622222222')
            ->setAdministrateur(false)
            ->setActif(true)
            ->setPassword($this->passwordHasher->hashPassword($user2, 'user2password'))
            ->setCampus($lyon)
            ->setFirstconnection(true);
        $manager->persist($user2);

        // Sauvegarde des utilisateurs dans la base
        $manager->flush();
    }

    public function getOrder(): int
    {
        return 6;
    }
}