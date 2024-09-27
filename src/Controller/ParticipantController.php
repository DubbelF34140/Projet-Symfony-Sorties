<?php

namespace App\Controller;

use App\Entity\Participant;
use App\Form\ChangePasswordType;
use App\Form\ParticipantEditType;
use App\Form\ParticipantRegisterType;
use App\Repository\EtatRepository;
use App\Repository\CampusRepository;
use App\Repository\ParticipantRepository;
use App\Repository\SortieRepository;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Constraint\IsTrue;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class ParticipantController extends AbstractController
{
    private CampusRepository $campusRepository;
    private ParticipantRepository $participantRepository;
    public function __construct(CampusRepository $campusRepository,
                                ParticipantRepository $participantRepository)
    {
        $this->campusRepository = $campusRepository;
        $this->participantRepository = $participantRepository;
    }
    #[Route('/participant/{id}/edit', name: 'app_participant_edit')]
    public function edit(Request $request, Participant $participant, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response {
        if (!$this->isGranted('ROLE_ADMIN') && $this->getUser() !== $participant) {
            // Redirection ou lancer une exception d'accès refusé
            throw new AccessDeniedException('Vous n\'avez pas la permission de modifier ce profil.');
        }

        $form = $this->createForm(ParticipantEditType::class, $participant, ['is_admin' => $this->isGranted('ROLE_ADMIN')]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('photo')->getData();
            if ($file) {
                // Gérer le stockage du fichier
                $filename = uniqid() . '.' . $file->guessExtension(); // Générer un nom de fichier unique
                $file->move($this->getParameter('photos_directory'), $filename); // Déplacez le fichier

                // Mettre à jour l'entité avec le nom du fichier
                $participant->setPhoto($filename);
            }

            // Sauvegarder les modifications
            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
        }

        return $this->render('participant/edit.html.twig', [
            'participant' => $participant,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/participant/{id}/change-password', name: 'app_participant_change_password')]
    public function changePassword(Request $request, Participant $participant, UserPasswordHasherInterface $userPasswordHasher, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(ChangePasswordType::class); // Assurez-vous de créer un formulaire ChangePasswordType.php
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $newPassword = $form->get('password')->getData();
            if (!empty($newPassword)) {
                $hashedPassword = $userPasswordHasher->hashPassword($participant, $newPassword);
                $participant->setPassword($hashedPassword);
            }

            $participant->setFirstconnection(false);
            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('app_participant_edit', ['id' => $participant->getId()]);
        }

        return $this->render('participant/change_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/participant/{id}/view', name: 'app_participant_view')]
    public function view(Participant $participant): Response
    {
        // Ici tu récupères déjà l'utilisateur à afficher via le param converter
        return $this->render('participant/view.html.twig', [
            'participant' => $participant,
        ]);
    }

    #[Route('/participant/admin', name: 'app_participant_admin')]
    public function admin(Request $request,
                          ParticipantRepository $participantRepository,
                          UserPasswordHasherInterface $passwordHasher,
                          EntityManagerInterface $em): Response
    {
        $participants = $participantRepository->findAll();

        $defaultData = ['message' => 'Récup File'];
        $form = $this->createFormBuilder($defaultData)
            ->add('fichier', FileType::class, [
                'required' => false,
//                'constraints' => [
//                    new Assert\Callback([
//                        // Ici $value prend la valeur du champs que l'on est en train de valider,
//                        // ainsi, pour un champs de type TextType, elle sera de type string.
//                        'callback' => static function (?FileType $value, ExecutionContextInterface $context) {
//                            if (!$value) {
//                                return;
//                            }
//
//                            if ($value->get('fichier')->getData()->getClientOriginalExtension() != "csv") {
//                                $context
//                                    ->buildViolation('Le fichier doit être un fichier csv')
//                                    ->atPath('[fichier]')
//                                    ->addViolation()
//                                ;
//                            }
//                        },
//                    ]),
//                ],
            ])
            ->getForm();

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            // data is an array with "name", "email", and "message" keys
            $file = $form->get('fichier')->getData();
            if ($file) {
                if($file->getClientOriginalExtension() != "csv"){
                    return $this->render('participant/admin.html.twig', [
                        'participants' => $participants,
                        'form' => $form->createView(),
                    ]);
                } else {
                    // Gérer le stockage du fichier
                    $filename = 'utilisateurs.csv'; // Générer un nom de fichier unique
                    $file->move($this->getParameter('fichierCSV_directory'), $filename); // Déplacez le fichier
                }
            }
            $row = 1;
            $datas[] = [];
            if (($csv= fopen("uploads/import/utilisateurs.csv", "r"))) {
                while (($data = fgetcsv($csv, 1000, ";"))) {
                    $datas[$row] = $data;
                    $row++;
                }
                fclose($csv);
            }
            for($i = 2; $i < count($datas); $i++){
                $userF = new Participant();
                $userF->setPseudo($datas[$i][2])
                    ->setEmail($datas[$i][8])
                    ->setNom($datas[$i][0])
                    ->setPrenom($datas[$i][1])
                    ->setTelephone($datas[$i][3])
                    ->setAdministrateur($datas[$i][4])
                    ->setActif($datas[$i][5])
                    ->setPassword($passwordHasher->hashPassword($userF, $datas[$i][10]))
                    ->setCampus($this->campusRepository->find($datas[$i][7]))
                    ->setFirstconnection($datas[$i][6]);
                $userF->setRoles($datas[$i][9] != "" ? [$datas[$i][9]] : []  );

                $userDB = ($this->participantRepository->findOneBy(['pseudo' => $userF->getPseudo()]));
                if(!$userDB){
                    $em->persist($userF);
                }
            }
            // Sauvegarde des utilisateurs dans la base
            $em->flush();
            return $this->redirectToRoute('app_participant_admin');
        }

        return $this->render('participant/admin.html.twig', [
            'participants' => $participants,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/participant/{id}/delete', name: 'app_participant_admin_delete')]
    public function admindelete(
        ParticipantRepository $participantRepository,
        SortieRepository $sortieRepository,
        EtatRepository $etatRepository,
        int $id,
        EntityManagerInterface $entityManager,
        Request $request
    ): Response {
        $participant = $participantRepository->find($id);

        if (!$participant) {
            // Si le participant n'existe pas, rediriger avec un message d'erreur
            $this->addFlash('error', 'Le participant n\'existe pas.');
            return $this->redirectToRoute('app_participant_admin', [], Response::HTTP_NOT_FOUND);
        }

        try {
            // Supprimer les associations du participant et le participant lui-même
            $participantRepository->removeParticipantIfNoSorties($participant, $entityManager, $sortieRepository);
            $this->addFlash('success', 'Le participant a été supprimé avec succès.');
        } catch (\Exception $e) {
            // Ajouter un message flash en cas d'erreur
            $this->addFlash('danger', $e->getMessage());
        }

        // Rediriger vers la liste des participants
        return $this->redirectToRoute('app_participant_admin');
    }



    #[Route('admin/participant/register', name: 'app_participant_register')]
    public function register(Request $request, EntityManagerInterface $em,  UserPasswordHasherInterface $passwordHasher): Response
    {
        $participant = new Participant();
        $form = $this->createForm(ParticipantRegisterType::class, $participant);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Génération du mot de passe : 3 premières lettres du prénom + 3 premières lettres du nom en minuscule sans accent
            $slugger = new AsciiSlugger();
            $prenomSlug = $slugger->slug(mb_substr($participant->getPrenom(), 0, 3))->lower();
            $nomSlug = $slugger->slug(mb_substr($participant->getNom(), 0, 3))->lower();
            $generatedPassword = $prenomSlug . $nomSlug;

            // Hashage du mot de passe
            $hashedPassword = $passwordHasher->hashPassword($participant, $generatedPassword);
            $participant->setPassword($hashedPassword);

            $em->persist($participant);
            $em->flush();

            return $this->redirectToRoute('app_participant_admin'); // Redirection vers la liste des participants
        }

        return $this->render('participant/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('admin/participant/register/csv', name: 'app_participant_register_csv', methods: ['POST'])]
    public function registerCSV(Request $request, EntityManagerInterface $em,  UserPasswordHasherInterface $passwordHasher): Response
    {
        $a = 2;
        dump($a);
        dump($request);


        $row = 1;
        $datas[] = [];
        if (($csv= fopen("uploads/import/utilisateurs.csv", "r"))) {

            while (($data = fgetcsv($csv, 1000, ";"))) {
                $num = count($data);
                $datas[$row] = $data;
                $row++;
            }
            fclose($csv);
        }
        //dump($datas);
        $users[]=[];
        for($i = 2; $i < count($datas); $i++){
            $userF = new Participant();
            $userF->setPseudo($datas[$i][2])
                ->setEmail($datas[$i][8])
                ->setNom($datas[$i][0])
                ->setPrenom($datas[$i][1])
                ->setTelephone($datas[$i][3])
                ->setAdministrateur($datas[$i][4])
                ->setActif($datas[$i][5])
                ->setPassword($passwordHasher->hashPassword($userF, $datas[$i][10]))
                ->setCampus($this->campusRepository->find($datas[$i][7]))
                ->setFirstconnection($datas[$i][6]);
            $userF->setRoles($datas[$i][9] != "" ? [$datas[$i][9]] : []  );
            dump($userF);

            $userDB = $this->participantRepository->findBy(['pseudo' => $userF->getPseudo()]);
            dump($userDB);
            if(!$userDB){
                $em->persist($userF);
                $users[] = $userF;
            }
        }
        dump($users);
        // Sauvegarde des utilisateurs dans la base
        $em->flush();

        return $this->redirectToRoute('app_participant_admin'); // Redirection vers la liste des participants
    }
}
