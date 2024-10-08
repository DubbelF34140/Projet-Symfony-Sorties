<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class VilleController extends AbstractController
{
    #[Route('/admin/ville', name: 'app_ville', methods: ['GET', 'POST'])]
    public function index(Request $request,
                          VilleRepository $villeRepository,
                          EntityManagerInterface $entityManager): Response
    {
        $filter = ['nom' => $request->query->get('nom')];

        $villes = $villeRepository->searchVilles($filter);

        $ville = new Ville();
        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$ville->getNom()) {
                $this->addFlash('danger', 'Le champ nom ne peut pas être vide.');
                return $this->redirectToRoute('app_ville');
            }
            if (!$ville->getCodePostal()) {
                $this->addFlash('danger', 'Le champ code postal ne peut pas être vide.');
                return $this->redirectToRoute('app_ville');
            }
            $entityManager->persist($ville);
            $entityManager->flush();
            return $this->redirectToRoute('app_ville');
        }

        return $this->render('ville/admin.html.twig', [
            'villes' => $villes,
            'form' => $form->createView(),
            'idVille' => 0
        ]);
    }

    #[Route('/admin/ville/{id<\d+>}', name: 'ville_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request,
                         Ville $ville,
                         EntityManagerInterface $entityManager,
                         VilleRepository $villeRepository): Response
    {
        $filter = ['nom' => $request->query->get('nom')];

        $villes = $villeRepository->searchVilles($filter);

        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$ville->getNom()) {
                $this->addFlash('danger', 'Le champ nom ne peut pas être vide.');
                return $this->redirectToRoute('ville_edit', ['id' => $ville->getId()]);
            }
            if (!$ville->getCodePostal()) {
                $this->addFlash('danger', 'Le champ code postal ne peut pas être vide.');
                return $this->redirectToRoute('ville_edit', ['id' => $ville->getId()]);
            }
            $entityManager->flush();
            return $this->redirectToRoute('app_ville');
        }

        return $this->render('ville/admin.html.twig', [
            'villes' => $villes,
            'form' => $form->createView(),
            'idVille' => $ville->getId()
        ]);
    }

    #[Route('/admin/ville/{id<\d+>}/delete', name: 'ville_delete', methods: ['POST'])]
    public function delete(Request $request, Ville $ville, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $ville->getId(), $request->request->get('_token'))) {
            $entityManager->remove($ville);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_ville');
    }
}
