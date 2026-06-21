<?php

namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: '`user`')]
#[ApiResource]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180, unique: true)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private string $email = '';

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column]
    private string $password = '';

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $displayName = null;

    #[ORM\OneToMany(targetEntity: UserSkillLevel::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $skillLevels;

    #[ORM\OneToMany(targetEntity: UserExerciseProgress::class, mappedBy: 'user', cascade: ['remove'])]
    private Collection $exerciseProgress;

    #[ORM\OneToMany(targetEntity: Partnership::class, mappedBy: 'user1', cascade: ['remove'])]
    private Collection $initiatedPartnerships;

    #[ORM\OneToMany(targetEntity: Partnership::class, mappedBy: 'user2')]
    private Collection $receivedPartnerships;

    public function __construct()
    {
        $this->skillLevels = new ArrayCollection();
        $this->exerciseProgress = new ArrayCollection();
        $this->initiatedPartnerships = new ArrayCollection();
        $this->receivedPartnerships = new ArrayCollection();
    }

    public function getId(): ?int { return $this->id; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $email): static { $this->email = $email; return $this; }

    public function getUserIdentifier(): string { return $this->email; }

    public function getRoles(): array
    {
        $roles = $this->roles;
        $roles[] = 'ROLE_USER';
        return array_unique($roles);
    }
    public function setRoles(array $roles): static { $this->roles = $roles; return $this; }

    public function getPassword(): string { return $this->password; }
    public function setPassword(string $password): static { $this->password = $password; return $this; }

    public function eraseCredentials(): void {}

    public function getDisplayName(): ?string { return $this->displayName; }
    public function setDisplayName(?string $displayName): static { $this->displayName = $displayName; return $this; }

    public function getSkillLevels(): Collection { return $this->skillLevels; }
    public function getExerciseProgress(): Collection { return $this->exerciseProgress; }
    public function getInitiatedPartnerships(): Collection { return $this->initiatedPartnerships; }
    public function getReceivedPartnerships(): Collection { return $this->receivedPartnerships; }

    public function addSkillLevel(UserSkillLevel $skillLevel): static
    {
        if (!$this->skillLevels->contains($skillLevel)) {
            $this->skillLevels->add($skillLevel);
            $skillLevel->setUser($this);
        }

        return $this;
    }

    public function removeSkillLevel(UserSkillLevel $skillLevel): static
    {
        if ($this->skillLevels->removeElement($skillLevel)) {
            // set the owning side to null (unless already changed)
            if ($skillLevel->getUser() === $this) {
                $skillLevel->setUser(null);
            }
        }

        return $this;
    }

    public function addExerciseProgress(UserExerciseProgress $exerciseProgress): static
    {
        if (!$this->exerciseProgress->contains($exerciseProgress)) {
            $this->exerciseProgress->add($exerciseProgress);
            $exerciseProgress->setUser($this);
        }

        return $this;
    }

    public function removeExerciseProgress(UserExerciseProgress $exerciseProgress): static
    {
        if ($this->exerciseProgress->removeElement($exerciseProgress)) {
            // set the owning side to null (unless already changed)
            if ($exerciseProgress->getUser() === $this) {
                $exerciseProgress->setUser(null);
            }
        }

        return $this;
    }

    public function addInitiatedPartnership(Partnership $initiatedPartnership): static
    {
        if (!$this->initiatedPartnerships->contains($initiatedPartnership)) {
            $this->initiatedPartnerships->add($initiatedPartnership);
            $initiatedPartnership->setUser1($this);
        }

        return $this;
    }

    public function removeInitiatedPartnership(Partnership $initiatedPartnership): static
    {
        if ($this->initiatedPartnerships->removeElement($initiatedPartnership)) {
            // set the owning side to null (unless already changed)
            if ($initiatedPartnership->getUser1() === $this) {
                $initiatedPartnership->setUser1(null);
            }
        }

        return $this;
    }

    public function addReceivedPartnership(Partnership $receivedPartnership): static
    {
        if (!$this->receivedPartnerships->contains($receivedPartnership)) {
            $this->receivedPartnerships->add($receivedPartnership);
            $receivedPartnership->setUser2($this);
        }

        return $this;
    }

    public function removeReceivedPartnership(Partnership $receivedPartnership): static
    {
        if ($this->receivedPartnerships->removeElement($receivedPartnership)) {
            // set the owning side to null (unless already changed)
            if ($receivedPartnership->getUser2() === $this) {
                $receivedPartnership->setUser2(null);
            }
        }

        return $this;
    }
}
