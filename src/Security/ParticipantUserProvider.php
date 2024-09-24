<?php

namespace App\Security;

use App\Entity\Participant;
use App\Repository\ParticipantRepository;
use Exception;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class ParticipantUserProvider implements UserProviderInterface
{
    private ParticipantRepository $participantRepository;

    public function __construct(ParticipantRepository $participantRepository)
    {
        $this->participantRepository = $participantRepository;
    }

    /**
     * @throws Exception
     */
    public function loadUserByIdentifier(string $identifier): UserInterface
    {
        $user = $this->participantRepository->findOneBy(['email' => $identifier])
            ?: $this->participantRepository->findOneBy(['pseudo' => $identifier]);

        if (!$user) {
            throw new Exception(sprintf('Utilisateur avec identifiant "%s" non trouvé.', $identifier));
        }

        return $user;
    }

    public function refreshUser(UserInterface $user): UserInterface
    {
        return $this->participantRepository->find($user->getId());
    }

    public function supportsClass(string $class): bool
    {
        return Participant::class === $class;
    }
}
