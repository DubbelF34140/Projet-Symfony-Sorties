<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Cookie;

class SessionTokenListener
{
    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $request = $event->getRequest();

        if ($request->attributes->get('_route') === 'api_login' && $response->isSuccessful()) {
            $session = $request->getSession();
            $sessionId = $session->getId();

            $cookie = Cookie::create('SESSID')
                ->withValue($sessionId)
                ->withPath('/')
                ->withSecure(true)
                ->withHttpOnly(true)
                ->withSameSite('strict');

            $response->headers->setCookie($cookie);
        }
    }
}