<?php

namespace App\Controller;

use App\Entity\Campus;
use App\Form\CampusType;
use App\Repository\CampusRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class MailController extends AbstractController
{
    #[Route('/api/mail', name: 'app_contact_participants', methods: ['POST'])]
    public function sendMail(Request $request, MailerInterface $mailer): JsonResponse
    {
        $selectedParticipants = $request->request->get('selectedParticipants');
        $subject = $request->request->get('subject');
        $messageContent = $request->request->get('message');

        if (empty($subject) || empty($messageContent)) {
            return $this->json(['error' => 'Le sujet et le message sont obligatoires.'], 400);
        }

        if (empty($selectedParticipants)) {
            return $this->json(['error' => 'Aucun participant sélectionné.'], 400);
        }

        $emails = explode(',', $selectedParticipants);

        foreach ($emails as $email) {
            if (str_ends_with($email, '@campus-eni.fr')) {
                $emailMessage = (new Email())
                    ->from('alexis.draud2023@campus-eni.fr')
                    ->to($email)
                    ->subject($subject)
                    ->text($messageContent);
                $mailer->send($emailMessage);
            }
        }

        return new JsonResponse(['message' => 'Emails envoyés avec succès'], Response::HTTP_OK);
    }
}
