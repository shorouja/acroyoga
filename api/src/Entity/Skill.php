<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Enum\SkillCategory;
use App\Repository\SkillRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: SkillRepository::class)]
#[ApiResource]
class Skill
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    #[Assert\NotBlank]
    private string $name = '';

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $abbreviation = null;

    #[ORM\Column(enumType: SkillCategory::class)]
    private SkillCategory $category = SkillCategory::Balance;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    #[ORM\ManyToMany(targetEntity: Exercise::class, mappedBy: 'skills')]
    private Collection $exercises;

    public function __construct()
    {
        $this->exercises = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getAbbreviation(): ?string { return $this->abbreviation; }
    public function setAbbreviation(?string $abbreviation): static { $this->abbreviation = $abbreviation; return $this; }

    public function getCategory(): SkillCategory { return $this->category; }
    public function setCategory(SkillCategory $category): static { $this->category = $category; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getExercises(): Collection { return $this->exercises; }

    public function addExercise(Exercise $exercise): static
    {
        if (!$this->exercises->contains($exercise)) {
            $this->exercises->add($exercise);
            $exercise->addSkill($this);
        }

        return $this;
    }

    public function removeExercise(Exercise $exercise): static
    {
        if ($this->exercises->removeElement($exercise)) {
            $exercise->removeSkill($this);
        }

        return $this;
    }
}
