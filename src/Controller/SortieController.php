<?php

namespace App\Controller;

use App\Entity\Groupes;
use App\Entity\Sortie;
use App\Form\AnnulationType;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\GroupsRepository;
use App\Repository\LieuRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    #[Route('/sortie', name: 'app_sortie')]
    public function index(
        Request $request,
        SortieRepository $sortieRepository,
        CampusRepository $campusRepository,
        EtatRepository $etatRepository,
        PaginatorInterface $paginator
    ): Response {
        $user = $this->getUser();

        $filters = [
            'campus' => $request->query->get('campus'),
            'nom' => $request->query->get('nom'),
            'dateDebut' => $request->query->get('dateDebut'),
            'dateFin' => $request->query->get('dateFin'),
            'organisateur' => $request->query->get('organisateur') ? $user : null,
            'inscrit' => $request->query->get('inscrit') ? $user : null,
            'nonInscrit' => $request->query->get('nonInscrit') ? $user : null,
            'terminees' => $request->query->get('terminees'),
        ];

        $campus = $request->query->get('campus', null);
        $campuss = $campusRepository->findAll();

        $query = $sortieRepository->searchSorties($user, $etatRepository, $filters);
        $page = max(1, $request->query->getInt('page', 1));

        $sorties = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        $totalPages = max(1, ceil($sorties->getTotalItemCount() / 10));

        dump($sorties);

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'campuss' => $campuss,
            'campus' => $campus,
            'currentPage' => $page,
            'totalPages' =>  $totalPages,
            'previousPage' => $page > 1 ? $page - 1 : null,
            'nextPage' => $page < ceil($sorties->getTotalItemCount() / 10) ? $page + 1 : null,

        ]);
    }

    #[Route('/sorties/create', name: 'app_sorties_create')]
    public function create(Request $request, SessionInterface $session, EntityManagerInterface $entityManager, EtatRepository $etatRepository, VilleRepository $villeRepository, LieuRepository $lieuRepository): Response
    {
        $villes = $villeRepository->findAll();
        $lieus = $lieuRepository->findAll();

        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $present = $form->get('present')->getData();
            if ($request->request->has('save')) {
                $sortie->setEtat($etatRepository->findEtatCreation());
            } elseif ($request->request->has('publish')) {
                $sortie->setEtat($etatRepository->findEtatPubliee());
            }
            $sortie->setOrganisateur($this->getUser());
            $sortie->setCampus($this->getUser()->getCampus());
            if ($present) {
                $sortie->addInscrit($this->getUser());
            }
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('app_sortie');
        }if (!$form->isSubmitted()) {
        if ($lieus) {
            $sortie->setLieu($lieus[0]);
        }
    }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
            'villes' => $villes,
            'sessionId' => $session->getId(),
            'sortie' => $sortie
        ]);
    }

    #[Route('/sorties/{id}/register', name: 'app_sorties_register')]
    public function register(int $id, SortieRepository $sortieRepository, EntityManagerInterface $entityManager): Response
    {
        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('La sortie n\'existe pas.');
        }

        $user = $this->getUser();

        if ($sortie->getInscrits()->contains($user) or $sortie->getPrivateParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà inscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie');
        } elseif ($sortie->getInscrits()->count() == $sortie->getNbInscriptionMax()) {
            $this->addFlash('error', 'La sortie est complète.');
            return $this->redirectToRoute('app_sortie');
        }

        $sortie->addInscrit($user);
        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', ('Vous êtes inscrit à la sortie: ' . $sortie->getNom()));

        return $this->redirectToRoute('app_sortie');
    }

    #[Route('/sorties/{id}/desister', name: 'app_sorties_desister')]
    public function desister(int $id, SortieRepository $sortieRepository, EntityManagerInterface $entityManager): Response
    {
        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            throw $this->createNotFoundException('La sortie n\'existe pas.');
        }

        $user = $this->getUser();

        if (!$sortie->getInscrits()->contains($user) and !$sortie->getPrivateParticipants()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà désinscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie');
        }

        if ($sortie->getInscrits()->contains($user)){
            $sortie->removeInscrit($user);
        }elseif ($sortie->getPrivateParticipants()->contains($user)){
            $sortie->removePrivateParticipant($user);
        }

        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', ('Vous êtes désinscrit à la sortie: ' . $sortie->getNom()));

        return $this->redirectToRoute('app_sortie');
    }

    #[Route('/sorties/{id<\d+>}/detail', name: 'app_sorties_detail')]
    public function detail(int $id, SessionInterface $session, SortieRepository $repo): Response
    {
        $sortie = $repo->findDetailSortie($id);

        if (!$sortie) {
            throw $this->createNotFoundException('La sortie n\'existe pas.');
        }

        return $this->render('sortie/detail.html.twig', [
            'title' => 'Afficher une sortie',
            'sortie' => $sortie,
            'sessionId' => $session->getId(),
            'participants' => $sortie->getInscrits(),
            'privateparticipants' => $sortie->getPrivateParticipants(),
        ]);
    }



    #[Route('sorties/{id<\d+>}/update', name: 'app_sorties_update', methods: ['GET', 'POST'])]
    public function update(Request $request,
                           SessionInterface $session,
                           Sortie $sortie,
                           sortieRepository $sortieRepo ,
                           etatRepository $etatRepository,
                           villeRepository $villeRepository,
                           EntityManagerInterface $em,
    ): Response
    {
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $villes = $villeRepository->findAll();
        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            if ($request->request->has('delete')) {
                $sortie->setEtat($etatRepository->findEtatAnnulee());
            } elseif ($request->request->has('publish')) {
                $sortie->setEtat($etatRepository->findEtatPubliee());
            }
            $em->flush();
            $this->addFlash('success', 'La sortie a été modifiée avec succès');
            return $this->redirectToRoute('app_sorties_detail', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/update.html.twig', [
            'title' => 'Modification d\'une sortie',
            'form' => $sortieForm,
            'sortie' => $sortie,
            'villes' => $villes,
            'sessionId' => $session->getId()
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'app_sorties_delete', methods: ['GET'])]
    public function delete(int $id, SortieRepository $repo, EntityManagerInterface $em): Response
    {
        $sortie = $repo->find($id);
        if(!$sortie){
            throw $this->createNotFoundException('Sortie not found');
        }

        $em->remove($sortie);
        $em->flush();

        return $this->redirectToRoute('app_sortie');
    }

    #[Route('/sortie/{id}/annuler', name: 'app_sortie_annuler', methods: ['GET', 'POST'])]
    public function annuler(
        Sortie $sortie,
        Request $request,
        EntityManagerInterface $entityManager,
        EtatRepository $etatRepository
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();

        if ($sortie->getOrganisateur() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez annuler que vos propres sorties.');
            return $this->redirectToRoute('app_sortie');
        }

        $now = new \DateTime();
        if ($sortie->getDateHeureDebut() <= $now) {
            $this->addFlash('error', 'Vous ne pouvez pas annuler une sortie qui a déjà commencé.');
            return $this->redirectToRoute('app_sortie');
        }

        $etatPublie = $etatRepository->findEtatPubliee();
        if ($sortie->getEtat() !== $etatPublie) {
            $this->addFlash('error', 'Seules les sorties publiées peuvent être annulées.');
            return $this->redirectToRoute('app_sortie');
        }

        $form = $this->createForm(AnnulationType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $etatAnnule = $etatRepository->findEtatAnnulee();
            $sortie->setEtat($etatAnnule);
            $sortie->setInfosuppr($form->get('infosuppr')->getData());

            $entityManager->persist($sortie);
            $entityManager->flush();

            $this->addFlash('success', 'La sortie a été annulée avec succès.');
            return $this->redirectToRoute('app_sortie');
        }

        return $this->render('sortie/annuler.html.twig', [
            'sortie' => $sortie,
            'form' => $form->createView(),
        ]);
    }


    /**
     * @throws \Exception
     */
    #[Route('/api/sorties', name: 'api_sorties', methods: ['POST'])]
    public function list(SortieRepository $sortieRepository, Request $request, EtatRepository $etatRepository): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new JsonResponse(['error' => 'Invalid JSON'], Response::HTTP_BAD_REQUEST);
        }

        $date = $data['date'] ?? null;
        $etat = $data['etat'] ?? null;

        if ($date) {
            try {
                $dateTime = new \DateTime($date);
            } catch (\Exception $e) {
                return new JsonResponse(['error' => 'Invalid date format.'], Response::HTTP_BAD_REQUEST);
            }
        } else {
            $dateTime = null;
        }

        $sorties = $sortieRepository->findByFilters($etatRepository, $etat, $dateTime);

        return $this->json($sorties, Response::HTTP_OK, [], ['groups' => 'sortie:list']);
    }

    #[Route('/admin/sortie/{id}/annuler', name: 'app_admin_sortie_annuler')]
    public function annulerSortie(
        int $id,
        SortieRepository $sortieRepository,
        EtatRepository $etatRepository,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $sortie = $sortieRepository->find($id);

        if (!$sortie) {
            return $this->redirectToRoute('app_sortie', [], Response::HTTP_NOT_FOUND);
        }

        $now = new \DateTime();
        if ($sortie->getDateHeureDebut() < $now) {
            $this->addFlash('error', 'La sortie a déjà commencé ou est terminée, elle ne peut pas être annulée.');
            return $this->redirectToRoute('app_sortie');
        }

        $etatAnnule = $etatRepository->findEtatAnnulee();
        if (!$etatAnnule) {
            throw new \Exception('État "Annulée" non trouvé dans la base de données.');
        }

        $sortie->setEtat($etatAnnule);

        $motifAnnulation = $request->request->get('motif_annulation');
        if ($motifAnnulation) {
            $sortie->setInfosuppr($motifAnnulation);
        }

        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'La sortie a été annulée avec succès.');
        return $this->redirectToRoute('app_sortie');
    }

    #[Route('/api/sorties/{id}/add-private-participant', name: 'api_add_private_participant', methods: ['POST'])]
    public function addPrivateParticipant(Request $request, Sortie $sortie, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $participant = $participantRepository->find($data['participantId']);

        if ($participant && !$sortie->getPrivateParticipants()->contains($participant)) {
            if ($sortie->getInscrits()->contains($participant)){
                $sortie->removeInscrit($participant);
            }
            $sortie->addPrivateParticipant($participant);
            $entityManager->flush();
            return new JsonResponse(['status' => 'Participant ajouté'], JsonResponse::HTTP_OK);
        }

        return new JsonResponse(['status' => 'Participant non trouvé ou déjà ajouté'], JsonResponse::HTTP_BAD_REQUEST);
    }

    #[Route('/api/sorties/{id}/remove-private-participant', name: 'api_remove_private_participant', methods: ['POST'])]
    public function removePrivateParticipant(Request $request, Sortie $sortie, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $participant = $participantRepository->find($data['participantId']);

        if ($participant && $sortie->getPrivateParticipants()->contains($participant)) {
            $sortie->removePrivateParticipant($participant);
            $entityManager->flush();
            return new JsonResponse(['status' => 'Participant retiré'], JsonResponse::HTTP_OK);
        }

        return new JsonResponse(['status' => 'Participant non trouvé ou déjà retiré'], JsonResponse::HTTP_BAD_REQUEST);
    }


    #[Route('/api/groups/search', name: 'api_groups_search', methods: ['GET'])]
    public function searchGroups(Request $request, GroupsRepository $groupsRepository): JsonResponse
    {
        $user = $this->getUser();
        $searchTerm = $request->query->get('q');

        if (empty($searchTerm)) {
            return $this->json([]);
        }

        $groups = $groupsRepository->searchByNameAndOwner($searchTerm, $user);

        $results = [];
        foreach ($groups as $group) {
            $results[] = [
                'id' => $group->getId(),
                'nom' => $group->getNom(),
            ];
        }

        return $this->json($results);
    }


    #[Route('/api/groups/{id}/participants', name: 'api_group_participants', methods: ['GET'])]
    public function getGroupParticipants(Groupes $group): JsonResponse
    {
        $participants = $group->getParticipants();

        $results = [];
        foreach ($participants as $participant) {
            $results[] = [
                'id' => $participant->getId(),
                'pseudo' => $participant->getPseudo(),
            ];
        }

        return $this->json($results);
    }


}