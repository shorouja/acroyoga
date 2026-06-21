<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\ExerciseGroupItemRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciseGroupItemRepository::class)]
#[ApiResource]
class ExerciseGroupItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ExerciseGroup::class, inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ExerciseGroup $group = null;

    #[ORM\ManyToOne(targetEntity: Exercise::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Exercise $exercise = null;

    #[ORM\Column]
    private int $position = 0;

    public function getId(): ?int { return $this->id; }

    public function getGroup(): ?ExerciseGroup { return $this->group; }
    public function setGroup(?ExerciseGroup $group): static { $this->group = $group; return $this; }

    public function getExercise(): ?Exercise { return $this->exercise; }
    public function setExercise(?Exercise $exercise): static { $this->exercise = $exercise; return $this; }

    public function getPosition(): int { return $this->position; }
    public function setPosition(int $position): static { $this->position = $position; return $this; }
}
