<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ChangePasswordType;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ParticipantController extends AbstractController
{
    #[Route('/participant/{id}/edit', name: 'app_participant_edit')]
    public function edit(Request $request, Participant $participant, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response {
        if ($this->getUser() !== $participant) {
            // Redirection ou lancer une exception d'accès refusé
            throw new AccessDeniedException('Vous n\'avez pas la permission de modifier ce profil.');
        }

        $form = $this->createForm(ParticipantType::class, $participant);
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
}
