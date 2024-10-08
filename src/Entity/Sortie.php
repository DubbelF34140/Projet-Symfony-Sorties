<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;


#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['sortie:list'])]
    private ?int $id = null;

    #[Assert\NotBlank(message: 'Le nom ne doit pas être vide')]
    #[ORM\Column(length: 255)]
    #[Groups(['sortie:list'])]
    private ?string $nom = null;

    #[Assert\NotBlank(message: 'La date ne doit pas être vide')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date de début doit être supérieure ou égale à aujourd\'hui')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['sortie:list'])]
    private ?\DateTimeInterface $dateHeureDebut = null;

    #[Assert\NotBlank(message: 'La date ne doit pas être vide')]
    #[Assert\LessThanOrEqual(propertyPath: 'dateHeureDebut', message: 'La date limite d\'inscription doit être inférieure ou égale à la date de début')]
    #[Assert\GreaterThanOrEqual('today', message: 'La date limite d\'inscription doit être supérieure ou égale à aujourd\'hui')]
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['sortie:list'])]
    private ?\DateTimeInterface $dateLimiteInscription = null;


    #[Assert\GreaterThanOrEqual('1', message: 'Le nombre de places doit être supérieur à 1' )]
    #[ORM\Column]
    #[Groups(['sortie:list'])]
    private ?int $nbInscriptionMax = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['sortie:list'])]
    private ?string $infosSortie = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sortie:list'])]
    private ?Etat $etat = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sortie:list'])]
    private ?Lieu $lieu = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: "SET NULL")]
    #[Groups(['sortie:list'])]
    private ?Participant $organisateur = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'inscriptions')]
    #[ORM\JoinTable(name: 'sortie_participant')]
    #[Groups(['sortie:list'])]
    private Collection $inscrits;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['sortie:list'])]
    private ?Campus $campus = null;

    #[Assert\Type(
        type: 'integer',
        message: 'The value {{ value }} is not a valid {{ type }}.',
    )]
    #[Assert\GreaterThanOrEqual('1', message: 'La durée (en min) doit être supérieure à 1' )]
    #[ORM\Column(type: 'integer')]
    #[Groups(['sortie:list'])]
    private ?int $duree = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $infosuppr = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'privateSorties')]
    #[ORM\JoinTable(name: 'sortie_private_participant')]
    private Collection $PrivateParticipants;

    public function __construct()
    {
        $this->inscrits = new ArrayCollection();
        $this->PrivateParticipants = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateHeureDebut(): ?\DateTimeInterface
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(\DateTimeInterface $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTimeInterface
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(\DateTimeInterface $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    public function getNbInscriptionMax(): ?int
    {
        return $this->nbInscriptionMax;
    }

    public function setNbInscriptionMax(int $nbInscriptionMax): static
    {
        $this->nbInscriptionMax = $nbInscriptionMax;

        return $this;
    }

    public function getInfosSortie(): ?string
    {
        return $this->infosSortie;
    }

    public function setInfosSortie(?string $infosSortie): static
    {
        $this->infosSortie = $infosSortie;

        return $this;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(?Etat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getInscrits(): Collection
    {
        return $this->inscrits;
    }

    public function addInscrit(Participant $inscrit): static
    {
        if (!$this->inscrits->contains($inscrit)) {
            $this->inscrits->add($inscrit);
        }

        return $this;
    }

    public function removeInscrit(Participant $inscrit): static
    {
        $this->inscrits->removeElement($inscrit);

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

    public function getDuree(): int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getInfosuppr(): ?string
    {
        return $this->infosuppr;
    }

    public function setInfosuppr(?string $infosuppr): static
    {
        $this->infosuppr = $infosuppr;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getPrivateParticipants(): Collection
    {
        return $this->PrivateParticipants;
    }

    public function addPrivateParticipant(Participant $privateParticipant): static
    {
        if (!$this->PrivateParticipants->contains($privateParticipant)) {
            $this->PrivateParticipants->add($privateParticipant);
        }

        return $this;
    }

    public function removePrivateParticipant(Participant $privateParticipant): static
    {
        $this->PrivateParticipants->removeElement($privateParticipant);

        return $this;
    }
}
