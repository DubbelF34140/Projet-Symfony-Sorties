<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ParticipantType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class ParticipantController extends AbstractController
{
    #[Route('/participant/{id}/edit', name: 'app_participant_edit')]
    public function edit(Request $request, Participant $participant, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response {
        $form = $this->createForm(ParticipantType::class, $participant);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifier l'unicité du pseudo et de l'email
            $existingParticipantByEmail = $entityManager->getRepository(Participant::class)->findOneBy(['email' => $participant->getEmail()]);
            $existingParticipantByPseudo = $entityManager->getRepository(Participant::class)->findOneBy(['pseudo' => $participant->getPseudo()]);

            if ($existingParticipantByEmail && $existingParticipantByEmail->getId() !== $participant->getId()) {
                $this->addFlash('error', 'Cet email est déjà utilisé.');
                return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
            }

            if ($existingParticipantByPseudo && $existingParticipantByPseudo->getId() !== $participant->getId()) {
                $this->addFlash('error', 'Ce pseudo est déjà utilisé.');
                return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
            }

            // Récupérer les mots de passe
            $plainPassword = $form->get('password')->getData();
            $confirmPassword = $form->get('confirm_password')->getData();

            // Vérifier si les mots de passe correspondent
            if (!empty($confirmPassword) && ($plainPassword && $plainPassword === $confirmPassword)) {
                // Changer le mot de passe
                $participant->setPassword($userPasswordHasher->hashPassword($participant, $plainPassword));
            } elseif (!empty($confirmPassword)) {
                $this->addFlash('error', 'Les mots de passe ne correspondent pas.');
                return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
            }

            // Enregistrer les changements
            $entityManager->persist($participant);
            $entityManager->flush();

            $this->addFlash('success', 'Profil mis à jour avec succès.');

            return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
        }

        return $this->render('participant/edit.html.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

}
