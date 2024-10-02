<?php

namespace App\Controller;

use App\Entity\Groups;
use App\Form\GroupType;
use App\Repository\GroupsRepository;
use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;

#[Route('/groups')]
class GroupsController extends AbstractController
{
    private EntityManagerInterface $entityManager;
    private Security $security;

    public function __construct(EntityManagerInterface $entityManager, Security $security)
    {
        $this->entityManager = $entityManager;
        $this->security = $security;
    }

    #[Route('/create_submit', name: 'group_create_submit', methods: ['POST'])]
    public function create(Request $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository): Response
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['nom']) || empty($data['nom'])) {
            return $this->json(['message' => 'Le nom du groupe est requis'], 400);
        }

        $group = new Groups();
        $group->setNom($data['nom']);
        $group->setOwner($this->getUser()); // L'utilisateur connecté est défini comme propriétaire

        // Ajouter les participants au groupe
        if (isset($data['participants'])) {
            foreach ($data['participants'] as $participantId) {
                $participant = $participantRepository->find($participantId);
                if ($participant) {
                    $group->addParticipant($participant);
                }
            }
        }

        $entityManager->persist($group);
        $entityManager->flush();

        return $this->json(['message' => 'Groupe créé avec succès']);
    }



    #[Route('/create', name: 'group_create', methods: ['GET'])]
    public function showCreateForm(): Response
    {
        $group = new Groups();
        $form = $this->createForm(GroupType::class, $group);  // Utilisation correcte de createForm()

        return $this->render('group/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    #[Route('/{id}/edit', name: 'group_edit')]
    public function edit(Request $request, Groups $group): Response
    {
        // Vérifier que seul le propriétaire peut modifier le groupe
        if ($group->getOwner() !== $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à modifier ce groupe.');
            return $this->redirectToRoute('group_list');
        }

        $form = $this->createForm(GroupType::class, $group);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', 'Groupe modifié avec succès !');

            return $this->redirectToRoute('group_list');
        }

        return $this->render('group/edit.html.twig', [
            'form' => $form->createView(),
            'group' => $group,
        ]);
    }

    #[Route('/{id}/delete', name: 'group_delete')]
    public function delete(Groups $group): Response
    {
        // Vérifier que seul le propriétaire peut supprimer le groupe
        if ($group->getOwner() !== $this->getUser()) {
            $this->addFlash('danger', 'Vous n\'êtes pas autorisé à supprimer ce groupe.');
            return $this->redirectToRoute('group_list');
        }

        $this->entityManager->remove($group);
        $this->entityManager->flush();

        $this->addFlash('success', 'Groupe supprimé avec succès !');

        return $this->redirectToRoute('group_list');
    }

    #[Route('/list', name: 'group_list')]
    public function list(GroupsRepository $groupsRepository): Response
    {
        $groups = $groupsRepository->findByOwner($this->getUser());

        return $this->render('group/list.html.twig', [
            'groups' => $groups,
        ]);
    }

    #[Route('/{id}/remove-participant/{participantId}', name: 'group_remove_participant', methods: ['POST'])]
    public function removeParticipant(Groups $group, int $participantId, ParticipantRepository $participantRepository): Response
    {
        // Chercher le participant via l'ID
        $participant = $participantRepository->find($participantId);

        if (!$participant) {
            return $this->json(['message' => 'Participant non trouvé'], 404);
        }

        // Vérifier si l'utilisateur connecté est le propriétaire du groupe
        if ($group->getOwner() !== $this->getUser()) {
            return $this->json(['message' => 'Non autorisé'], 403);
        }

        // Retirer le participant du groupe
        $group->removeParticipant($participant);
        $this->entityManager->flush();

        return $this->json(['message' => 'Participant retiré avec succès']);
    }


    #[Route('/{id}/add-participant', name: 'group_add_participant', methods: ['POST'])]
    public function addParticipant(Request $request, Groups $group, ParticipantRepository $participantRepository): Response
    {
        // Récupérer les données envoyées en JSON
        $data = json_decode($request->getContent(), true);

        if (!isset($data['participantId'])) {
            return $this->json(['message' => 'Participant ID manquant'], 400);
        }

        $participantId = $data['participantId'];

        // Chercher le participant via l'ID
        $participant = $participantRepository->find($participantId);

        if (!$participant) {
            return $this->json(['message' => 'Participant non trouvé'], 404);
        }

        // Ajouter le participant au groupe si l'utilisateur connecté est le propriétaire
        if ($group->getOwner() === $this->getUser()) {
            $group->addParticipant($participant);
            $this->entityManager->flush();

            return $this->json(['message' => 'Participant ajouté avec succès']);
        }

        return $this->json(['message' => 'Vous n\'êtes pas autorisé à ajouter des participants à ce groupe'], 403);
    }


}