<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\Role\RoleHierarchyInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[UniqueEntity(fields: ['username'], message: 'There is already an account with this username')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
  #[ORM\Id]
  #[ORM\GeneratedValue]
  #[ORM\Column]
  private ?int $id = null;

  #[ORM\Column(length: 180, unique: true)]
  private ?string $username = null;

  #[ORM\Column]
  private array $roles = [];

  /**
   * @var string The hashed password
   */
  #[ORM\Column]
  private ?string $password = null;

  #[ORM\Column(type: 'string', length: 180, unique: true)]
  private ?string $email = null;

  #[ORM\Column(type: 'boolean')]
  private $isVerified = false;

  #[ORM\Column(length: 255, nullable: true)]
  private ?string $salt = null;

  #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
  private ?\DateTimeInterface $last_login = null;

  #[ORM\Column]
  private ?bool $locked = false;

  public function getId(): ?int
  {
    return $this->id;
  }

  public function getUsername(): ?string
  {
    return $this->username;
  }

  public function setUsername(string $username): static
  {
    $this->username = $username;

    return $this;
  }

  /**
   * A visual identifier that represents this user.
   *
   * @see UserInterface
   */
  public function getUserIdentifier(): string
  {
    return (string) $this->username;
  }

  /**
   * @see UserInterface
   */
  public function getRoles(): array
  {
    $roles = $this->roles;
    // guarantee every user at least has ROLE_USER
    $roles[] = 'ROLE_USER';

    return array_unique($roles);
  }

  public function setRoles(array $roles): static
  {
    $this->roles = $roles;

    return $this;
  }

  public function addRole($role)
  {
    $role = strtoupper($role);
    if ($role === 'ROLE_USER') {
      return $this;
    }

    if (!in_array($role, $this->roles, true)) {
      $this->roles[] = $role;
    }

    return $this;
  }

  public function removeRole($role)
  {
    if (false !== $key = array_search(strtoupper($role), $this->roles, true)) {
      unset($this->roles[$key]);
      $this->roles = array_values($this->roles);
    }

    return $this;
  }

  public function hasRole(string $role, RoleHierarchyInterface $role_hierarchy)
  {
    return in_array(
      $role,
      $role_hierarchy->getReachableRoleNames($this->getRoles())
    );
  }

  /**
   * @see PasswordAuthenticatedUserInterface
   */
  public function getPassword(): string
  {
    return $this->password;
  }

  public function setPassword(string $password): static
  {
    $this->password = $password;

    return $this;
  }

  /**
   * @see UserInterface
   */
  public function eraseCredentials(): void
  {
    // If you store any temporary, sensitive data on the user, clear it here
    // $this->plainPassword = null;
  }

  public function getEmail(): ?string
  {
    return $this->email;
  }

  public function setEmail(string $email): static
  {
    $this->email = $email;

    return $this;
  }

  public function isVerified(): bool
  {
    return $this->isVerified;
  }

  public function setIsVerified(bool $isVerified): static
  {
    $this->isVerified = $isVerified;

    return $this;
  }

  public function getSalt(): ?string
  {
    return $this->salt;
  }

  public function setSalt(?string $salt): static
  {
    $this->salt = $salt;

    return $this;
  }

  public function getLastLogin(): ?\DateTimeInterface
  {
    return $this->last_login;
  }

  public function setLastLogin(?\DateTimeInterface $last_login): static
  {
    $this->last_login = $last_login;

    return $this;
  }

  public function isLocked(): ?bool
  {
    return $this->locked;
  }

  public function setLocked(bool $locked): static
  {
    $this->locked = $locked;

    return $this;
  }
}
