<?php

namespace App\Entity;

use App\Repository\BeerRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Intl\Countries;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BeerRepository::class)]
class Beer
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  #[Groups(['show_map'])]
  private ?int $id = null;

  #[ORM\ManyToOne]
  private User $user;

  #[ORM\Column(length: 512)]
  #[Assert\NotBlank]
  #[Groups(['show_map'])]
  private ?string $name = null;

  #[ORM\Column(length: 512, nullable: true)]
  private ?string $path = null;

  #[ORM\Column(length: 255)]
  #[Assert\NotBlank]
  #[Assert\Country]
  #[Groups(['show_map'])]
  private ?string $country = null;

  #[ORM\Column]
  #[Assert\NotBlank]
  #[Assert\Range(
    min: 0,
    max: 100,
    notInRangeMessage: 'Abv must be between 0-100%',
  )]
  private ?float $abv = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE)]
  private ?\DateTimeInterface $dateadded = null;

  #[ORM\Column]
  private ?bool $issignature = null;

  #[ORM\Column]
  private ?bool $deleted = null;

  #[ORM\ManyToOne]
  private ?User $modifiedby = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
  private ?\DateTimeInterface $datemodified = null;

  #[ORM\Column(length: 512, nullable: true)]
  private ?string $deleted_reason = null;

  #[ORM\Column(length: 512, nullable: true)]
  private ?string $bucket = null;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getUser(): ?User
  {
    return $this->user;
  }

  public function setUser(?User $user): static
  {
    $this->user = $user;

    return $this;
  }

  public function getName(): ?string
  {
    return $this->name;
  }

  public function setName(string $name): static
  {
    $this->name = $name;

    return $this;
  }

  public function getPath(): ?string
  {
    return $this->path;
  }

  public function setPath(?string $path): static
  {
    $this->path = $path;

    return $this;
  }

  public function getAbv(): ?float
  {
    return $this->abv;
  }

  public function setAbv(float $abv): static
  {
    $this->abv = $abv;

    return $this;
  }

  public function getDateadded(): ?\DateTimeInterface
  {
    return $this->dateadded;
  }

  public function setDateadded(\DateTimeInterface $dateadded): static
  {
    $this->dateadded = $dateadded;

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

  public function isIssignature(): ?bool
  {
    return $this->issignature;
  }

  public function setIssignature(bool $issignature): static
  {
    $this->issignature = $issignature;

    return $this;
  }

  public function isDeleted(): ?bool
  {
    return $this->deleted;
  }

  public function setDeleted(bool $deleted): static
  {
    $this->deleted = $deleted;

    return $this;
  }

  public function getDeletedReason(): ?string
  {
    return $this->deleted_reason;
  }

  public function setDeletedReason(?string $deletedReason): static
  {
    $this->deleted_reason = $deletedReason;

    return $this;
  }

  public function getBucket(): ?string
  {
    return $this->bucket;
  }

  public function setBucket(?string $bucket): static
  {
    $this->bucket = $bucket;

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

  public function getCountry(): ?string
  {
    return $this->country;
  }

  public function setCountry(string $country): static
  {
    $this->country = $country;

    return $this;
  }

  public function getCountryName(): ?string
  {
    return Countries::getName($this->getCountry());
  }
}
