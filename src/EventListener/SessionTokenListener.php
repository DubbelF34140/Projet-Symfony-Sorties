<?php

namespace App\EventListener;

use App\Repository\ParticipantRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Core\Security;

#[AsEventListener(event: ResponseEvent::class, method: 'onKernelResponse')]
class SessionTokenListener
{
    private $security;
    private $participantRepository;
    private $entityManager;
    private $logger;

    public function __construct(Security $security, ParticipantRepository $participantRepository, EntityManagerInterface $entityManager, LoggerInterface $logger) {
        $this->security = $security;
        $this->participantRepository = $participantRepository;
        $this->entityManager = $entityManager;
        $this->logger = $logger;
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();
        $response = $event->getResponse();
        $session = $request->getSession();
        $user = $this->security->getUser();

//        if ($user && !$request->cookies->has('SESSID')) {
//            if (!$session->isStarted()) {
//                $session->start();
//            }
//            $sessionId = $session->getId();
//
//            $cookie = new Cookie(
//                'SESSID',
//                $sessionId,
//                time() + 3600,
//                '/',
//                null,
//                false,
//                true,
//                false,
//                'Strict'
//            );
//            $response->headers->setCookie($cookie);
//        }

        if($user) {
            $user2 = $this->participantRepository->findOneBy(['email' => $user->getUserIdentifier()]);
            if($user2->getSessionId() !== $session->getId()) {
                $user2->setSessionId($session->getId());
                $this->entityManager->persist($user2);
                $this->entityManager->flush();
            }
        }
    }
}