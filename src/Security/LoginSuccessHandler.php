<?php
// src/Security/LoginSuccessHandler.php

namespace App\Security;

use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;
    private $security;

    private ParticipantRepository $participantRepository;

    private EntityManagerInterface $entityManager;

    public function __construct(RouterInterface $router, Security $security, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager)
    {
        $this->router = $router;
        $this->security = $security;
        $this->participantRepository = $participantRepository;
        $this->entityManager = $entityManager;
    }

    public function onAuthenticationSuccess(Request $request, $token): RedirectResponse
    {
        // Récupérer l'utilisateur connecté
        $user = $this->security->getUser();

        if ($user->isFirstConnection()) {
            // Si l'utilisateur est en première connexion, redirige vers la page de changement de mot de passe
            return new RedirectResponse($this->router->generate('app_participant_change_password', [
                'id' => $user->getId(),
            ]));
        }

        $sessionId = $request->getSession()->getId();
        $cookie = Cookie::create('SESSID', $sessionId)
        ->withHttpOnly(true);

        $user2 = $this->participantRepository->findOneBy(['id' => $user->getId()]);
        $user2->setSessionId($sessionId);

        $this->entityManager->persist($user2);
        $this->entityManager->flush();

        $response = new RedirectResponse($this->router->generate('app_sortie'));
        $response->headers->setCookie($cookie);

        return $response;
    }
}
