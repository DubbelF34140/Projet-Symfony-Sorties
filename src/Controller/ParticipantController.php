<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ChangePasswordType;
use App\Form\ParticipantEditType;
use App\Form\ParticipantRegisterType;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\AsciiSlugger;

class ParticipantController extends AbstractController
{
    #[Route('/participant/{id}/edit', name: 'app_participant_edit')]
    public function edit(Request $request, Participant $participant, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $participant) {
            // Redirection ou lancer une exception d'accès refusé
            throw new AccessDeniedException('Vous n\'avez pas la permission de modifier ce profil.');
        }

        $form = $this->createForm(ParticipantEditType::class, $participant, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('photo')->getData();
            if ($file) {
                // Gérer le stockage du fichier
                $filename = uniqid() . '.' . $file->guessExtension(); // Générer un nom de fichier unique
                $file->move($this->getParameter('photos_directory'), $filename); // Déplacez le fichier

                // Mettre à jour l'entité avec le nom du fichier
                $participant->setPhoto($filename);
            }

            // Sauvegarder les modifications
            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
        }

        return $this->render('participant/edit.html.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/participant/{id}/change-password', name: 'app_participant_change_password')]
    public function changePassword(Request $request, Participant $participant, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChangePasswordType::class); // Assurez-vous de créer un formulaire ChangePasswordType.php
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if (!empty($newPassword)) {
                $hashedPassword = $userPasswordHasher->hashPassword($participant, $newPassword);
                $participant->setPassword($hashedPassword);
            }

            $participant->setFirstconnection(false);
            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
        }

        return $this->render('participant/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/participant/{id}/view', name: 'app_participant_view')]
    public function view(Participant $participant): Response
    {
        // Ici tu récupères déjà l'utilisateur à afficher via le param converter
        return $this->render('participant/view.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/participant/admin', name: 'app_participant_admin')]
    public function admin(ParticipantRepository $participantRepository): Response
    {
        $participants = $participantRepository->findAll();

        return $this->render('participant/admin.html.twig', [
            'participants' => $participants,
        ]);
    }

    #[Route('admin/participant/register', name: 'app_participant_register')]
    public function register(Request $request, EntityManagerInterface $em,  UserPasswordHasherInterface $passwordHasher): Response
    {
        $participant = new Participant();
        $form = $this->createForm(ParticipantRegisterType::class, $participant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du mot de passe : 3 premières lettres du prénom + 3 premières lettres du nom en minuscule sans accent
            $slugger = new AsciiSlugger();
            $prenomSlug = $slugger->slug(mb_substr($participant->getPrenom(), 0, 3))->lower();
            $nomSlug = $slugger->slug(mb_substr($participant->getNom(), 0, 3))->lower();
            $generatedPassword = $prenomSlug . $nomSlug;

            // Hashage du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($participant, $generatedPassword);
            $participant->setPassword($hashedPassword);

            $em->persist($participant);
            $em->flush();

            return $this->redirectToRoute('app_participant_admin'); // Redirection vers la liste des participants
        }

        return $this->render('participant/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
