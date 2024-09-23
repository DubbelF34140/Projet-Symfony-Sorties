<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    #[Route('/sortie', name: 'app_sortie')]
    public function index(Request $request, SortieRepository $sortieRepository, CampusRepository $campusRepository, EtatRepository $etatRepository): Response
    {
        // Récupérer l'utilisateur connecté
        $user = $this->getUser();

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
        $sorties = $sortieRepository->searchSorties($filters, $etatRepository);

        return $this->render('sortie/index.html.twig', [
            'sorties' => $sorties,
            'campuss' => $campuss,
            'campus' => $campus,
        ]);
    }

    #[Route('/sorties/create', name: 'app_sorties_create')]
    public function create(Request $request, EntityManagerInterface $entityManager, EtatRepository $etatRepository): Response
    {
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
        }

        $sortie->addInscrit($user);
        $entityManager->persist($sortie);
        $entityManager->flush();

        $this->addFlash('success', 'Vous êtes inscrit à la sortie.');

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

        $this->addFlash('success', 'Vous êtes désinscrit à la sortie.');

        return $this->redirectToRoute('app_sortie');
    }


    #[Route('/sorties/{id}', name: 'app_sorties_show')]
    public function show(int $id, SortieRepository $sortieRepository,): Response
    {

        $sortie = $sortieRepository->find($id);

        return $this->render('sortie/show.html.twig', [
            'sortie' => $sortie,
        ]);
    }

}

