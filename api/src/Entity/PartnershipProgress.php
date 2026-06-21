<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\ProgressStatus;
use App\Enum\Role;
use App\Repository\PartnershipProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PartnershipProgressRepository::class)]
#[ORM\UniqueConstraint(fields: ['partnership', 'exercise', 'role'])]
#[ApiResource]
class PartnershipProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Partnership::class, inversedBy: 'progress')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Partnership $partnership = null;

    #[ORM\ManyToOne(targetEntity: Exercise::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercise $exercise = null;

    /** Which role user1 is playing in this exercise within this partnership */
    #[ORM\Column(enumType: Role::class)]
    private Role $role = Role::Both;

    #[ORM\Column(enumType: ProgressStatus::class)]
    private ProgressStatus $status = ProgressStatus::Learning;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $masteredAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getPartnership(): ?Partnership { return $this->partnership; }
    public function setPartnership(?Partnership $partnership): static { $this->partnership = $partnership; return $this; }

    public function getExercise(): ?Exercise { return $this->exercise; }
    public function setExercise(?Exercise $exercise): static { $this->exercise = $exercise; return $this; }

    public function getRole(): Role { return $this->role; }
    public function setRole(Role $role): static { $this->role = $role; return $this; }

    public function getStatus(): ProgressStatus { return $this->status; }
    public function setStatus(ProgressStatus $status): static
    {
        $this->status = $status;
        $this->updatedAt = new \DateTimeImmutable();
        if ($status === ProgressStatus::Mastered && $this->masteredAt === null) {
            $this->masteredAt = new \DateTimeImmutable();
        }
        return $this;
    }

    public function getMasteredAt(): ?\DateTimeImmutable { return $this->masteredAt; }
    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }

    public function setMasteredAt(?\DateTimeImmutable $masteredAt): static
    {
        $this->masteredAt = $masteredAt;

        return $this;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
