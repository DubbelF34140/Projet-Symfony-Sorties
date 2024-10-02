<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class CampusController extends AbstractController
{
    #[Route('/admin/campus', name: 'app_campus', methods: ['GET', 'POST'])]
    public function index(Request $request, CampusRepository $campusRepository, EntityManagerInterface $entityManager): Response
    {
        $filter = ['nom' => $request->query->get('nom')];

        $campuses = $campusRepository->searchCampuses($filter);

        $campus = new Campus();
        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$campus->getNom()) {
                $this->addFlash('danger', 'Le champ nom ne peut pas être vide.');
                return $this->redirectToRoute('app_campus');
            }
            $entityManager->persist($campus);
            $entityManager->flush();

            return $this->redirectToRoute('app_campus');
        }

        return $this->render('campus/admin.html.twig', [
            'campuses' => $campuses,
            'form' => $form->createView(),
            'idCampus' => 0
        ]);
    }

    #[Route('/admin/campus/{id<\d+>}', name: 'campus_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Campus $campus,
                         EntityManagerInterface $entityManager,
                         CampusRepository $campusRepository): Response
    {
        $filter = ['nom' => $request->query->get('nom')];

        $campuses = $campusRepository->searchCampuses($filter);

        $form = $this->createForm(CampusType::class, $campus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            if (!$campus->getNom()) {
                $this->addFlash('danger', 'Le champ nom ne peut pas être vide.');
                return $this->redirectToRoute('campus_edit', ['id' => $campus->getId()]);
            }
            $entityManager->flush();
            return $this->redirectToRoute('app_campus');
        }


        return $this->render('campus/admin.html.twig', [
            'campuses' => $campuses,
            'form' => $form->createView(),
            'idCampus' => $campus->getId()
        ]);
    }

    #[Route('admin/campus/{id<\d+>}/delete', name: 'campus_delete', methods: ['POST'])]
    public function delete(Request $request, Campus $campus, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $campus->getId(), $request->request->get('_token'))) {
            $entityManager->remove($campus);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_campus');
    }
}
