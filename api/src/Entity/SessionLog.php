<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\SessionLogRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SessionLogRepository::class)]
#[ApiResource]
class SessionLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Partnership::class, inversedBy: 'sessions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Partnership $partnership = null;

    #[ORM\Column(type: 'date_immutable')]
    private \DateTimeImmutable $sessionDate;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->sessionDate = new \DateTimeImmutable();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getPartnership(): ?Partnership { return $this->partnership; }
    public function setPartnership(?Partnership $partnership): static { $this->partnership = $partnership; return $this; }

    public function getSessionDate(): \DateTimeImmutable { return $this->sessionDate; }
    public function setSessionDate(\DateTimeImmutable $sessionDate): static { $this->sessionDate = $sessionDate; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): static { $this->notes = $notes; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }
}
