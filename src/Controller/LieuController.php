<?php

namespace App\Controller;

use App\Entity\Lieu;
use App\Entity\Ville;
use App\Repository\LieuRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
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
