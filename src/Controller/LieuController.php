<?php

namespace App\Controller;

use App\Repository\LieuRepository;
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
        // Récupérer les lieux par ville
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
}
