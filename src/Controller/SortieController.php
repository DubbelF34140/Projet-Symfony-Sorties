<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\AnnulationType;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use App\Repository\VilleRepository;
use App\Service\SortieStatusService;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    #[Route('/sortie', name: 'app_sortie')]
    public function index(
        Request $request,
        SortieRepository $sortieRepository,
        CampusRepository $campusRepository,
        EtatRepository $etatRepository,
        SortieStatusService $service,
        LoggerInterface $logger,
        PaginatorInterface $paginator
    ): Response {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

        $service->checkSortieStatus($logger, $etatRepository);

        // Récupérer les filtres depuis la requête GET
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

        // Recherche des sorties avec les filtres appliqués
        $query = $sortieRepository->searchSorties($etatRepository, $filters);

        dump($query);

        // Pagination des résultats
        $sorties = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            10
        );

        dump($sorties);

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'campuss' => $campuss,
            'campus' => $campus,
        ]);
    }

    #[Route('/sorties/create', name: 'app_sorties_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository, VilleRepository $villeRepository): Response
    {
        $villes = $villeRepository->findAll();

        $sortie = new Sortie();
        $form = $this->createForm(SortieType::class, $sortie);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            if ($request->request->has('save')) {
                $sortie->setEtat($etatRepository->findEtatCreation());
            } elseif ($request->request->has('publish')) {
                $sortie->setEtat($etatRepository->findEtatPubliee());
            }

            $sortie->setOrganisateur($this->getUser());
            $sortie->setCampus($this->getUser()->getCampus());
            dump($sortie);
            $entityManager->persist($sortie);
            $entityManager->flush();

            return $this->redirectToRoute('app_sortie');
        }

        return $this->render('sortie/create.html.twig', [
            'form' => $form->createView(),
            'villes' => $villes,
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

        if ($sortie->getInscrits()->contains($user)) {
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

        if (!$sortie->getInscrits()->contains($user)) {
            $this->addFlash('warning', 'Vous êtes déjà désinscrit à cette sortie.');
            return $this->redirectToRoute('app_sortie');
        }

        $sortie->removeInscrit($user);
        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', ('Vous êtes désinscrit à la sortie: ' . $sortie->getNom()));

        return $this->redirectToRoute('app_sortie');
    }

    #[Route('/sorties/{id<\d+>}/detail', name: 'app_sorties_detail')]
    public function detail(int $id, SortieRepository $repo): Response
    {

        $sortie = $repo->find($id);
        $participants = $sortie->getInscrits();

        if(!$repo){
            throw $this->createNotFoundException('sortie not found');
        }

        return $this->render('sortie/detail.html.twig', [
            'title' => 'Afficher une sortie',
            'sortie' => $sortie,
            'participants' => $participants,

        ]);
    }

    #[Route('sorties/{id<\d+>}/update', name: 'app_sorties_update', methods: ['GET', 'POST'])]
    public function update(Request $request,
                           Sortie $sortie,
                           sortieRepository $sortieRepo ,
                           etatRepository $etatRepository,
                           villeRepository $villeRepository,
                           EntityManagerInterface $em//,
                           //SluggerInterface $slugger,
                           //#[Autowire('%kernel.project_dir%/public/uploads/wish')] string $wishesDirectory
    ): Response
    {
        $sortieForm = $this->createForm(SortieType::class, $sortie);
        $villes = $villeRepository->findAll();
        //Submit and Validate
        $sortieForm->handleRequest($request);
        if ($sortieForm->isSubmitted() && $sortieForm->isValid()) {
            if ($request->request->has('delete')) {
                $sortie->setEtat($etatRepository->findEtatAnnulee());
            } elseif ($request->request->has('publish')) {
                $sortie->setEtat($etatRepository->findEtatPubliee());
            }
            // Persist
            $em->flush();
            //Add Succes notif
            $this->addFlash('success', 'La sortie a été modifiée avec succès');
            return $this->redirectToRoute('app_sorties_detail', ['id' => $sortie->getId()]);
        }

        return $this->render('sortie/update.html.twig', [
            'title' => 'Modification d\'une sortie',
            'form' => $sortieForm,
            'sortie' => $sortie,
            'villes' => $villes,
        ]);
    }

    #[Route('/delete/{id<\d+>}', name: 'app_sorties_delete', methods: ['POST'])]
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
        // Vérification si l'utilisateur est l'organisateur de la sortie
        $this->denyAccessUnlessGranted('ROLE_USER');
        $user = $this->getUser();

        if ($sortie->getOrganisateur() !== $user) {
            $this->addFlash('error', 'Vous ne pouvez annuler que vos propres sorties.');
            return $this->redirectToRoute('app_sortie');
        }

        // Vérification si la sortie est déjà commencée
        $now = new \DateTime();
        if ($sortie->getDateHeureDebut() <= $now) {
            $this->addFlash('error', 'Vous ne pouvez pas annuler une sortie qui a déjà commencé.');
            return $this->redirectToRoute('app_sortie');
        }

        // Vérification si la sortie est publiée
        $etatPublie = $etatRepository->findEtatPubliee();
        if ($sortie->getEtat() !== $etatPublie) {
            $this->addFlash('error', 'Seules les sorties publiées peuvent être annulées.');
            return $this->redirectToRoute('app_sortie');
        }

        // Formulaire pour motif d'annulation
        $form = $this->createForm(AnnulationType::class, $sortie);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Changer l'état à "Annulée"
            $etatAnnule = $etatRepository->findEtatAnnulee();
            $sortie->setEtat($etatAnnule);
            $sortie->setInfosuppr($form->get('infosuppr')->getData());

            // Sauvegarder le motif d'annulation
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
        // Récupérer les données JSON envoyées dans la requête
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

        // Utiliser le repository pour filtrer les sorties
        $sorties = $sortieRepository->findByFilters($etatRepository, $etat, $dateTime);

        // Retourner les résultats en JSON
        return $this->json($sorties, Response::HTTP_OK, [], ['groups' => 'sortie:list']);
    }

}