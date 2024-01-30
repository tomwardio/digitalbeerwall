<?php

namespace App\Entity;

use App\Repository\RemovedCountryRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Serializer\Annotation\Groups;

#[ORM\Entity(repositoryClass: RemovedCountryRepository::class)]
class RemovedCountry
{
  #[ORM\Id]
  #[ORM\Column(length: 32)]
  #[Assert\NotBlank]
  #[Groups(['show_map'])]
  private ?string $code = null;

  #[ORM\ManyToOne]
  #[ORM\JoinColumn(nullable: false)]
  private ?User $removedby = null;

  #[ORM\ManyToOne]
  private ?User $modifiedby = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
  private ?\DateTimeInterface $datemodified = null;

  #[ORM\Column(length: 512, nullable: true)]
  #[Groups(['show_map'])]
  private ?string $reason = null;

  public function getCode(): ?string
  {
    return $this->code;
  }

  public function setCode(string $code): static
  {
    $this->code = $code;

    return $this;
  }

  public function getRemovedby(): ?User
  {
    return $this->removedby;
  }

  public function setRemovedby(?User $removedby): static
  {
    $this->removedby = $removedby;

    return $this;
  }

  public function getModifiedby(): ?User
  {
    return $this->modifiedby;
  }

  public function setModifiedby(?User $modifiedby): static
  {
    $this->modifiedby = $modifiedby;

    return $this;
  }

  public function getDatemodified(): ?\DateTimeInterface
  {
    return $this->datemodified;
  }

  public function setDatemodified(?\DateTimeInterface $datemodified): static
  {
    $this->datemodified = $datemodified;

    return $this;
  }

  public function getReason(): ?string
  {
    return $this->reason;
  }

  public function setReason(?string $reason): static
  {
    $this->reason = $reason;

    return $this;
  }

  public function getCountryName(): ?string
  {
    return Countries::getName($this->getCode());
  }
}
