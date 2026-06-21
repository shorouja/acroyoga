<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\ProgressStatus;
use App\Enum\Role;
use App\Repository\UserExerciseProgressRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserExerciseProgressRepository::class)]
#[ORM\UniqueConstraint(fields: ['user', 'exercise', 'role'])]
#[ApiResource]
class UserExerciseProgress
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'exerciseProgress')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Exercise::class, inversedBy: 'userProgress')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercise $exercise = null;

    /** The role (base/flyer) this progress entry tracks */
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

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

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
