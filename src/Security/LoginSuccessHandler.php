<?php
// src/Security/LoginSuccessHandler.php

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;

class LoginSuccessHandler implements AuthenticationSuccessHandlerInterface
{
    private $router;
    private $security;

    public function __construct(RouterInterface $router, Security $security)
    {
        $this->router = $router;
        $this->security = $security;
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

        // Sinon, redirige vers la page d'accueil
        return new RedirectResponse($this->router->generate('app_sortie'));
    }
}
