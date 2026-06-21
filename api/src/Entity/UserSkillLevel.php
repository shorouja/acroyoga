<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\Difficulty;
use App\Repository\UserSkillLevelRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserSkillLevelRepository::class)]
#[ORM\UniqueConstraint(fields: ['user', 'skill'])]
#[ApiResource]
class UserSkillLevel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'skillLevels')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(targetEntity: Skill::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Skill $skill = null;

    #[ORM\Column(enumType: Difficulty::class)]
    private Difficulty $level = Difficulty::Beginner;

    #[ORM\Column]
    private \DateTimeImmutable $achievedAt;

    public function __construct()
    {
        $this->achievedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getUser(): ?User { return $this->user; }
    public function setUser(?User $user): static { $this->user = $user; return $this; }

    public function getSkill(): ?Skill { return $this->skill; }
    public function setSkill(?Skill $skill): static { $this->skill = $skill; return $this; }

    public function getLevel(): Difficulty { return $this->level; }
    public function setLevel(Difficulty $level): static { $this->level = $level; return $this; }

    public function getAchievedAt(): \DateTimeImmutable { return $this->achievedAt; }
    public function setAchievedAt(\DateTimeImmutable $achievedAt): static { $this->achievedAt = $achievedAt; return $this; }
}
