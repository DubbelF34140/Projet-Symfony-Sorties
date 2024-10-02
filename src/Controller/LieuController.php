<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Sortie;
use App\Entity\Ville;
use App\Form\LieuType;
use App\Form\SortieType;
use App\Repository\LieuRepository;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

class LieuController extends AbstractController
{
    #[Route('/lieu', name: 'app_lieu')]
    public function index(): Response
    {
        return $this->render('lieu/index.html.twig', [
            'controller_name' => 'LieuController',
        ]);
    }

    #[Route('/lieu/add/{idVille}', name: 'app_add_lieu', methods: ['GET', 'POST'])]
    public function add(Request $request,
                        int $idVille,
                        EntityManagerInterface $entityManager,
                        VilleRepository $villeRepository,
                        SessionInterface $session): Response
    {
        dump($idVille);
        $ville = $villeRepository->find($idVille);
        $lieu = new Lieu();
        $lieu->setVille($ville);
        $form = $this->createForm(LieuType::class, $lieu);
        $form->handleRequest($request);
        dump($lieu);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($lieu);
            $entityManager->flush();

            $sortie = new Sortie();
            $sortie->setLieu($lieu);
            $form = $this->createForm(SortieType::class, $sortie);
            $form->handleRequest($request);
            return $this->redirectToRoute('app_sorties_create',
                ['lieu_id' => $lieu->getId(), 'ville_id' => $ville->getId()]);
        }
        return $this->render('lieu/add.html.twig', [
            'form' => $form->createView(),
            'lieu' => $lieu,
            'ville' => $ville,
        ]);
    }

    #[Route('/api/lieux/{villeId}', name: 'api_lieux', methods: ['GET'])]
    public function getLieux(int $villeId, LieuRepository $lieuRepository): JsonResponse
    {
        $lieux = $lieuRepository->findBy(['ville' => $villeId]);

        $lieuxData = [];
        foreach ($lieux as $lieu) {
            $lieuxData[] = [
                'id' => $lieu->getId(),
                'nom' => $lieu->getNom(),
            ];
        }

        return new JsonResponse($lieuxData);
    }

    #[Route('/api/lieux/add', name: 'api_lieux_add', methods: ['POST'])]
    public function addLieu(Request $request, EntityManagerInterface $entityManager): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        $lieu = new Lieu();
        $lieu->setNom($data['nom']);
        $lieu->setRue($data['rue']);
        $lieu->setLatitude($data['latitude']);
        $lieu->setLongitude($data['longitude']);

        // Récupération de la ville par son ID
        $ville = $entityManager->getRepository(Ville::class)->find($data['ville']);
        $lieu->setVille($ville);

        $entityManager->persist($lieu);
        $entityManager->flush();

        return new JsonResponse([
            'id' => $lieu->getId(),
            'nom' => $lieu->getNom(),
        ]);
    }

}
