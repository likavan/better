<?php

namespace App\Entity;

use App\Repository\FailRepository;
use Doctrine\ORM\Mapping as ORM;
use Doctrine\ORM\Mapping\HasLifecycleCallbacks;

#[ORM\Entity(repositoryClass: FailRepository::class), HasLifecycleCallbacks]
class Fail implements  TimestampableInterface
{
    use TimestampableTrait;
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'fails')]
    private ?Improve $improve = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getImprove(): ?Improve
    {
        return $this->improve;
    }

    public function setImprove(?Improve $improve): static
    {
        $this->improve = $improve;

        return $this;
    }
}
