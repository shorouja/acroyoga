<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\Difficulty;
use App\Enum\Role;
use App\Repository\ExerciseRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ExerciseRepository::class)]
#[ApiResource]
class Exercise
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $abbreviation = null;

    #[ORM\Column(enumType: Difficulty::class)]
    private Difficulty $difficulty = Difficulty::Beginner;

    /** Roles this exercise involves (base, flyer, both, solo) */
    #[ORM\Column(enumType: Role::class)]
    private Role $role = Role::Both;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Skill::class, inversedBy: 'exercises')]
    private Collection $skills;

    #[ORM\OneToMany(targetEntity: UserExerciseProgress::class, mappedBy: 'exercise')]
    private Collection $userProgress;

    public function __construct()
    {
        $this->skills = new ArrayCollection();
        $this->userProgress = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAbbreviation(): ?string { return $this->abbreviation; }
    public function setAbbreviation(?string $abbreviation): static { $this->abbreviation = $abbreviation; return $this; }

    public function getDifficulty(): Difficulty { return $this->difficulty; }
    public function setDifficulty(Difficulty $difficulty): static { $this->difficulty = $difficulty; return $this; }

    public function getRole(): Role { return $this->role; }
    public function setRole(Role $role): static { $this->role = $role; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getSkills(): Collection { return $this->skills; }
    public function addSkill(Skill $skill): static
    {
        if (!$this->skills->contains($skill)) {
            $this->skills->add($skill);
        }
        return $this;
    }
    public function removeSkill(Skill $skill): static
    {
        $this->skills->removeElement($skill);
        return $this;
    }

    public function getUserProgress(): Collection { return $this->userProgress; }

    public function addUserProgress(UserExerciseProgress $userProgress): static
    {
        if (!$this->userProgress->contains($userProgress)) {
            $this->userProgress->add($userProgress);
            $userProgress->setExercise($this);
        }

        return $this;
    }

    public function removeUserProgress(UserExerciseProgress $userProgress): static
    {
        if ($this->userProgress->removeElement($userProgress)) {
            // set the owning side to null (unless already changed)
            if ($userProgress->getExercise() === $this) {
                $userProgress->setExercise(null);
            }
        }

        return $this;
    }
}
