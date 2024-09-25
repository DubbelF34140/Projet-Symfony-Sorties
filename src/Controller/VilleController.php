<?php

namespace App\Controller;

use App\Entity\Ville;
use App\Form\VilleType;
use App\Repository\VilleRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class VilleController extends AbstractController
{
    #[Route('/admin/ville', name: 'app_ville')]
    public function index(Request $request, EntityManagerInterface $entityManager): Response
    {

        $ville = new Ville();
        $form = $this->createForm(VilleType::class, $ville);

        $form->handleRequest($request);
        dump($ville);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($ville);
            $entityManager->flush();
            return $this->redirectToRoute('app_ville', ['up' => false]);
        }

        $villes = $entityManager->getRepository(Ville::class)->findAll();

        return $this->render('ville/index.html.twig', [
            'villes' => $villes,
            'form' => $form->createView(),
            'up' => false
        ]);
    }

//    #[Route('/admin/ville/create', name: 'app_admin_ville_create', methods: ['POST'])]
//    public function create(Request $request,EntityManagerInterface $entityManager): Response
//    {
//        dump($request);
//        //$ville = new Ville();
//        $form = $this->createForm(VilleType::class, $ville);
//
//        $form->handleRequest($request);
//        dump($ville);
//
//        if ($form->isSubmitted() && $form->isValid()) {
//            $entityManager->persist($ville);
//            $entityManager->flush();
//            return $this->redirectToRoute('app_ville', ['up' => false]);
//        }
//
//        $villes = $entityManager->getRepository(Ville::class)->findAll();
//
//        return $this->redirectToRoute('app_ville', ['up' => false]);
//    }




    #[Route('/admin/ville/{id<\d+>}/update', name: 'app_admin_ville_update')]
    public function update(int $id, Request $request,
                           VilleRepository $villeRepository,
                           EntityManagerInterface $entityManager): Response
    {
        $ville = $villeRepository->find($id);
        $form = $this->createForm(VilleType::class, $ville);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // Persist
            $entityManager->flush();
            //Add Succes notif
            $this->addFlash('success', 'La sortie a été modifiée avec succès');
            return $this->redirectToRoute('app_sorties_detail', ['id' => $ville->getId()]);
        }

        $up = true;
        return $this->redirectToRoute('app_ville');
    }

    #[Route('/admin/ville/{id<\d+>}/delete', name: 'app_admin_ville_delete')]
    public function delete(int $id, VilleRepository $villeRepository, EntityManagerInterface $entityManager): Response
    {
        $ville = $villeRepository->find($id);
        if(!$ville){
            throw $this->createNotFoundException('Ville not found');
        }
        $entityManager->remove($ville);
        $entityManager->flush();

        return $this->redirectToRoute('app_ville');
    }
}
