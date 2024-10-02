<?php

namespace App\Entity;

use App\Repository\ParticipantRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Entity\Groupes;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: ParticipantRepository::class)]
#[UniqueEntity(fields: ['email'], message: 'Cet email est déjà utilisé')]
#[UniqueEntity(fields: ['pseudo'], message: 'Ce pseudo est déjà utilisé')]
class Participant implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'L\'adresse email ne doit pas être vide')]
    #[Assert\Email(message: 'L\'adresse email n\'est pas valide.')]
    #[ORM\Column(length: 180, unique: true)]
    private ?string $email = null;

    #[Assert\NotBlank(message: 'Le pseudo ne doit pas être vide')]
    #[ORM\Column(length: 255, unique: true)]
    #[Groupes(['sortie:list'])]
    private ?string $pseudo = null;

    #[ORM\Column(nullable: true)]
    private ?string $photo = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    #[Assert\NotBlank(message: 'Le nom ne doit pas être vide')]
    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[Assert\NotBlank(message: 'Le prénom ne doit pas être vide')]
    #[ORM\Column(length: 255)]
    private ?string $prenom = null;

    #[Assert\NotBlank(message: 'Le téléphone ne doit pas être vide')]
    #[ORM\Column(length: 255)]
    private ?string $telephone = null;

    #[ORM\Column]
    private bool $administrateur = false;

    #[ORM\Column]
    private bool $actif = true;

    #[ORM\ManyToOne(inversedBy: 'participants')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $campus = null;

    #[ORM\Column]
    private bool $firstconnection = true;

    #[ORM\Column(nullable: true)]
    private ?string $sessionId;

    /**
     * @var Collection<int, Sortie>
     */
    #[ORM\ManyToMany(targetEntity: Sortie::class, mappedBy: 'PrivateParticipants')]
    private Collection $PrivateParticipants;

    /**
     * @var Collection<int, Groupes>
     */
    #[ORM\ManyToMany(targetEntity: Groupes::class, mappedBy: 'Groupes')]
    private Collection $groups;

    public function __construct()
    {
        $this->PrivateParticipants = new ArrayCollection();
        $this->groups = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPseudo(): ?string
    {
        return $this->pseudo;
    }

    public function setPseudo(?string $pseudo): static
    {
        $this->pseudo = $pseudo;

        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    /**
     * @see UserInterface
     *
     * @return list<string>
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        if ($password) {
            $this->password = $password;
        }

        return $this;
    }

    /**
     * @see UserInterface
     */
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
        // $this->plainPassword = null;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(?string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;

        return $this;
    }

    public function isAdministrateur(): ?bool
    {
        return $this->administrateur;
    }

    public function setAdministrateur(bool $administrateur): static
    {
        $this->administrateur = $administrateur;

        return $this;
    }

    public function isActif(): bool
    {
        return $this->actif;
    }

    public function setActif(bool $actif): static
    {
        $this->actif = $actif;

        return $this;
    }

    public function getCampus(): ?Campus
    {
        return $this->campus;
    }

    public function setCampus(?Campus $campus): static
    {
        $this->campus = $campus;

        return $this;
    }

    public function isFirstconnection(): ?bool
    {
        return $this->firstconnection;
    }

    public function setFirstconnection(bool $firstconnection): static
    {
        $this->firstconnection = $firstconnection;

        return $this;
    }

    public function getSessionId(): ?string
    {
        return $this->sessionId;
    }

    public function setSessionId(string $sessionId): static
    {
        $this->sessionId = $sessionId;

        return $this;
    }

    /**
     * @return Collection<int, Sortie>
     */
    public function getPrivateParticipants(): Collection
    {
        return $this->PrivateParticipants;
    }

    public function addPrivateParticipant(Sortie $privateParticipant): static
    {
        if (!$this->PrivateParticipants->contains($privateParticipant)) {
            $this->PrivateParticipants->add($privateParticipant);
            $privateParticipant->addPrivateParticipant($this);
        }

        return $this;
    }

    public function removePrivateParticipant(Sortie $privateParticipant): static
    {
        if ($this->PrivateParticipants->removeElement($privateParticipant)) {
            $privateParticipant->removePrivateParticipant($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Groupes>
     */
    public function getGroups(): Collection
    {
        return $this->groups;
    }

    public function addGroup(Groupes $group): static
    {
        if (!$this->groups->contains($group)) {
            $this->groups->add($group);
            $group->addParticipant($this);
        }

        return $this;
    }

    public function removeGroup(Groupes $group): static
    {
        if ($this->groups->removeElement($group)) {
            $group->removeParticipant($this);
        }

        return $this;
    }
}
