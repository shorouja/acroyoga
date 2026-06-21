<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\PartnershipStatus;
use App\Repository\PartnershipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartnershipRepository::class)]
#[ApiResource]
class Partnership
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'initiatedPartnerships')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user1 = null;

    /** Null when tracking solo or the partner hasn't joined yet */
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'receivedPartnerships')]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $user2 = null;

    /** Name of the partner when user2 has no account */
    #[ORM\Column(length: 100, nullable: true)]
    private ?string $partnerAlias = null;

    #[ORM\Column(enumType: PartnershipStatus::class)]
    private PartnershipStatus $status = PartnershipStatus::OneSided;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(targetEntity: PartnershipProgress::class, mappedBy: 'partnership', cascade: ['remove'])]
    private Collection $progress;

    #[ORM\OneToMany(targetEntity: SessionLog::class, mappedBy: 'partnership', cascade: ['remove'])]
    private Collection $sessions;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->progress = new ArrayCollection();
        $this->sessions = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser1(): ?User { return $this->user1; }
    public function setUser1(?User $user1): static { $this->user1 = $user1; return $this; }

    public function getUser2(): ?User { return $this->user2; }
    public function setUser2(?User $user2): static { $this->user2 = $user2; return $this; }

    public function getPartnerAlias(): ?string { return $this->partnerAlias; }
    public function setPartnerAlias(?string $partnerAlias): static { $this->partnerAlias = $partnerAlias; return $this; }

    public function getStatus(): PartnershipStatus { return $this->status; }
    public function setStatus(PartnershipStatus $status): static { $this->status = $status; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getProgress(): Collection { return $this->progress; }
    public function getSessions(): Collection { return $this->sessions; }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function addProgress(PartnershipProgress $progress): static
    {
        if (!$this->progress->contains($progress)) {
            $this->progress->add($progress);
            $progress->setPartnership($this);
        }

        return $this;
    }

    public function removeProgress(PartnershipProgress $progress): static
    {
        if ($this->progress->removeElement($progress)) {
            // set the owning side to null (unless already changed)
            if ($progress->getPartnership() === $this) {
                $progress->setPartnership(null);
            }
        }

        return $this;
    }

    public function addSession(SessionLog $session): static
    {
        if (!$this->sessions->contains($session)) {
            $this->sessions->add($session);
            $session->setPartnership($this);
        }

        return $this;
    }

    public function removeSession(SessionLog $session): static
    {
        if ($this->sessions->removeElement($session)) {
            // set the owning side to null (unless already changed)
            if ($session->getPartnership() === $this) {
                $session->setPartnership(null);
            }
        }

        return $this;
    }
}
