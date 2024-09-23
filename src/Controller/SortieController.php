<?php

namespace App\Controller;

use App\Entity\Sortie;
use App\Form\SortieType;
use App\Repository\CampusRepository;
use App\Repository\EtatRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class SortieController extends AbstractController
{
    #[Route('/sortie', name: 'app_sortie')]
    public function index(Request $request, SortieRepository $sortieRepository, CampusRepository $campusRepository): Response
    {
        // Récupérer les filtres depuis la requête GET
        $filters = [
            'campus' => $request->query->get('campus'),
            'nom' => $request->query->get('nom'),
            'dateDebut' => $request->query->get('dateDebut'),
            'dateFin' => $request->query->get('dateFin'),
            'organisateur' => $request->query->get('organisateur'),
            'inscrit' => $request->query->get('inscrit'),
            'nonInscrit' => $request->query->get('nonInscrit'),
            'terminees' => $request->query->get('terminees'),
        ];
        $campus = $request->query->get('campus', null);
        $campuss = $campusRepository->findAll();

        // Appel au repository pour filtrer les sorties en fonction des critères
        $sorties = $sortieRepository->searchSorties($filters);

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

}

