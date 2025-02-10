<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping\Column;
use Doctrine\ORM\Mapping\PrePersist;
use Doctrine\ORM\Mapping\PreUpdate;

trait TimestampableTrait
{
    #[Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $createdAt = null;

    #[Column(type: Types::DATE_IMMUTABLE)]
    private ?DateTimeImmutable $updatedAt = null;

    public function getCreatedAt(): ?DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(?DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[PrePersist, PreUpdate]
    public function updateTimestamps(): void
    {
        $now = new DateTimeImmutable();

        if (null === $this->getCreatedAt()) {
            $this->setCreatedAt($now);
        }

        $this->setUpdatedAt($now);
    }
}
