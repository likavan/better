<?php

namespace App\Entity;

use App\Repository\ImproveGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ImproveGroupRepository::class)]
class ImproveGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT)]
    private ?string $title = null;

    /**
     * @var Collection<int, Improve>
     */
    #[ORM\OneToMany(targetEntity: Improve::class, mappedBy: 'improveGroup')]
    private Collection $improves;

    public function __construct()
    {
        $this->improves = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * @return Collection<int, Improve>
     */
    public function getImproves(): Collection
    {
        return $this->improves;
    }

    public function addImprove(Improve $improve): static
    {
        if (!$this->improves->contains($improve)) {
            $this->improves->add($improve);
            $improve->setImproveGroup($this);
        }

        return $this;
    }

    public function removeImprove(Improve $improve): static
    {
        if ($this->improves->removeElement($improve)) {
            // set the owning side to null (unless already changed)
            if ($improve->getImproveGroup() === $this) {
                $improve->setImproveGroup(null);
            }
        }

        return $this;
    }

    public function __toString(): string
    {
        return $this->getTitle();
    }
}
