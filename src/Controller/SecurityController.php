<?php

namespace App\Controller;

use App\Entity\ResetToken;
use App\Form\ResetPasswordFormType;
use App\Form\ResetPasswordRequestFormType;
use App\Repository\ParticipantRepository;
use App\Repository\ResetTokenRepository;
use Doctrine\ORM\EntityManagerInterface;
use Random\RandomException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class SecurityController extends AbstractController
{
    #[Route(path: '/login', name: 'app_login')]
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();

        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('security/login.html.twig', [
            'last_username' => $lastUsername,
            'error' => $error,
        ]);
    }

    #[Route(path: '/logout', name: 'app_logout')]
    public function logout(): void
    {
        throw new \LogicException('This method can be blank - it will be intercepted by the logout key on your firewall.');
    }

    #[Route('/api/login', name: 'api_login', methods: ['POST'])]
    public function apiLogin(AuthenticationUtils $authenticationUtils): JsonResponse
    {
        $error = $authenticationUtils->getLastAuthenticationError();
        $lastUsername = $authenticationUtils->getLastUsername();

        if ($error) {
            return $this->json([
                'message' => 'Authentication failed',
                'error' => $error->getMessage()
            ], Response::HTTP_UNAUTHORIZED);
        }

        return $this->json([
            'message' => 'Login successful',
            'user' => $this->getUser()->getUserIdentifier(),
        ]);
    }

    #[Route('/api/logout', name: 'api_logout', methods: ['POST'])]
    public function apiLogout(): JsonResponse
    {
        return $this->json([
            'message' => 'Login successful',
            'user' => $this->getUser()->getUserIdentifier(),
        ]);
    }

    /**
     * @throws RandomException
     * @throws TransportExceptionInterface
     * @throws RandomException
     */
    #[Route('/reset_password', name: 'app_reset_password_request')]
    public function resetPasswordRequest(Request $request, EntityManagerInterface $entityManager, ParticipantRepository $participantRepository, MailerInterface $mailer): Response
    {
        $form = $this->createForm(ResetPasswordRequestFormType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $email = $form->get('email')->getData();

            // Vérifiez si l'utilisateur existe
            $user = $participantRepository->findOneBy(['email' => $email]);

            if ($user) {
                // Générer un token de réinitialisation
                $token = bin2hex(random_bytes(32));

                // Sauvegarder le token et son expiration en base de données ou en session (à implémenter)
                $resetToken = new ResetToken();
                $resetToken->setUserId($user->getId());
                $resetToken->setToken($token);
                $resetToken->setExpiration((new \DateTime())->add(new \DateInterval('PT1H')));
                $entityManager->persist($resetToken);
                $entityManager->flush();

                // Créer le lien de réinitialisation
                $resetUrl = $this->generateUrl('app_reset_password', ['token' => $token], true);

                // Envoyer l'e-mail
                $email = (new Email())
                    ->from('divydium@outlook.com')
                    ->to($user->getEmail())
                    ->subject('Réinitialisation de votre mot de passe')
                    ->html('<p>Pour réinitialiser votre mot de passe, veuillez cliquer sur ce lien : <a href="' . $resetUrl . '">' . $resetUrl . '</a></p>');

                $mailer->send($email);
            }

            return $this->render('security/reset_password_request_success.html.twig'); // Vue de succès
        }

        return $this->render('security/reset_password_request.html.twig', [
            'form' => $form->createView(),
        ]);
    }


    #[Route('/reset_password/{token}', name: 'app_reset_password')]
    public function resetPassword(Request $request, EntityManagerInterface $entityManager, ResetTokenRepository $resetTokenRepository, ParticipantRepository $participantRepository, UserPasswordHasherInterface $userPasswordHasher, string $token): Response
    {
        $form = $this->createForm(ResetPasswordFormType::class);
        $form->handleRequest($request);

        $participant = null;
        $tokenFound = $resetTokenRepository->findOneBy(['token' => $token]);
        if ($tokenFound && $tokenFound->getExpiration() > new \DateTime('now')) {
            $participant = $participantRepository->findOneBy(['id' => $tokenFound->getUserId()]);
        }

        if ($form->isSubmitted() && $form->isValid() && $participant) {
            $password = $form->get('password')->getData();
            if (!empty($password)) {
                $hashedPassword = $userPasswordHasher->hashPassword($participant, $password);
                $participant->setPassword($hashedPassword);
                $entityManager->remove($tokenFound);
            }

            $entityManager->persist($participant);
            $entityManager->flush();

            return $this->redirectToRoute('app_login');
        }

        return $this->render('security/reset_password_reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
