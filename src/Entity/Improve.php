<?php

namespace App\Entity;

use App\Repository\ImproveRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: ImproveRepository::class), HasLifecycleCallbacks]
class Improve implements  TimestampableInterface
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1000)]
    private ?string $Title = null;

    /**
     * @var Collection<int, Fail>
     */
    #[ORM\OneToMany(targetEntity: Fail::class, mappedBy: 'improve')]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $fails;

    public function __construct()
    {
        $this->fails = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->Title;
    }

    public function setTitle(string $Title): static
    {
        $this->Title = $Title;

        return $this;
    }

    /**
     * @return Collection<int, Fail>
     */
    public function getFails(): Collection
    {
        return $this->fails;
    }

    public function addFail(Fail $fail): static
    {
        if (!$this->fails->contains($fail)) {
            $this->fails->add($fail);
            $fail->setImprove($this);
        }

        return $this;
    }

    public function removeFail(Fail $fail): static
    {
        if ($this->fails->removeElement($fail)) {
            // set the owning side to null (unless already changed)
            if ($fail->getImprove() === $this) {
                $fail->setImprove(null);
            }
        }

        return $this;
    }
}
