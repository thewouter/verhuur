<?php

declare(strict_types=1);

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Entity;

use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\Common\Collections\ArrayCollection;

/**
 * @ORM\Entity(repositoryClass="App\Repository\UserRepository")
 * @ORM\Table(name="user")
 *
 * Defines the properties of the User entity to represent the application users.
 * See https://symfony.com/doc/current/book/doctrine.html#creating-an-entity-class
 *
 * Tip: if you have an existing database, you can generate these entity class automatically.
 * See https://symfony.com/doc/current/cookbook/doctrine/reverse_engineering.html
 *
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 * @author Wouter van Harten <wouter@woutervanharten.nl>
 */
class User implements UserInterface, \Serializable {

    public function __construct() {
        $this->leases = new ArrayCollection();
        $this->confirmed = false;
    }
    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="full_name", type="string", length=255, nullable=false)
     */
    private $fullName;

    /**
     * @var string
     *
     * @ORM\Column(name="username", type="string", length=255, nullable=false)
     */
    private $username;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=255, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="password", type="string", length=255, nullable=false)
     */
    private $password;

    /**
     * @var json
     *
     * @ORM\Column(name="roles", type="json", nullable=false)
     */
    private $roles;

    /**
     * @var string
     *
     * @ORM\Column(name="address", type="text", length=255, nullable=false)
     */
    private $address;

    /**
     * @var string
     *
     * @ORM\Column(name="phone", type="text", length=255, nullable=false)
     */
    private $phone;

    /**
     * @var string|null
     *
     * @ORM\Column(name="password_reset", type="text", nullable=true)
     */
    private $password_reset;

    /**
     * @var int
     *
     * @ORM\Column(name="confirmed", type="integer", nullable=false)
     */
    private $confirmed;

    /**
     * @var Collection
     *
     * @ORM\OneToMany(targetEntity="App\Entity\LeaseRequest", mappedBy="author")
     * @ORM\OrderBy({
     *     "publishedAt"="DESC"
     * })
     */
    private $leases;

    public function getId(): ?int {
        return $this->id;
    }

    public function setFullName(string $fullName): void {
        $this->fullName = $fullName;
    }

    public function getFullName(): ?string {
        return $this->fullName;
    }

    public function getUsername(): ?string {
        return $this->username;
    }

    public function setUsername(string $username): void {
        $this->username = $username;
    }

    public function getEmail(): ?string {
        return $this->email;
    }

    public function setEmail(string $email): void {
        $this->email = $email;
    }

    public function getPassword(): ?string {
        return $this->password;
    }

    public function setPassword(string $password): void {
        $this->password = $password;
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array {
        $roles = $this->roles;

        // guarantees that a user always has at least one role for security
        if (empty($roles)) {
            $roles[] = 'ROLE_USER';
        }

        return array_unique($roles);
    }

    public function setRoles(array $roles): void {
        $this->roles = $roles;
    }

    public function addRole(string $role): void {
        if (!in_array($role, $this->roles)) {
            $this->roles[] = $role;
        }
    }

    /**
     * Returns the salt that was originally used to encode the password.
     *
     * {@inheritdoc}
     */
    public function getSalt(): ?string {
        // See "Do you need to use a Salt?" at https://symfony.com/doc/current/cookbook/security/entity_provider.html
        // we're using bcrypt in security.yml to encode the password, so
        // the salt value is built-in and you don't have to generate one

        return null;
    }

    /**
     * Removes sensitive data from the user.
     *
     * {@inheritdoc}
     */
    public function eraseCredentials(): void {
        // if you had a plainPassword property, you'd nullify it here
        // $this->plainPassword = null;
    }

    /**
     * {@inheritdoc}
     */
    public function serialize(): string {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        return serialize([$this->id, $this->username, $this->password]);
    }

    /**
     * {@inheritdoc}
     */
    public function unserialize($serialized): void {
        // add $this->salt too if you don't use Bcrypt or Argon2i
        [$this->id, $this->username, $this->password] = unserialize($serialized, ['allowed_classes' => false]);
    }

    /**
     * @return Collection|LeaseRequest[]
     */
    public function getLeases(): Collection {
        return $this->leases;
    }

    public function addLease(LeaseRequest $lease): self {
        if (!$this->leases->contains($lease)) {
            $this->leases[] = $lease;
            $lease->setAuthor($this);
        }

        return $this;
    }

    public function removeLease(LeaseRequest $lease): self {
        if ($this->leases->contains($lease)) {
            $this->leases->removeElement($lease);
            // set the owning side to null (unless already changed)
            if ($lease->getAuthor() === $this) {
                $lease->setAuthor(null);
            }
        }

        return $this;
    }

    public function hasLease(LeaseRequest $lease): bool {
        return in_array($lease, $this->leases->toArray());
    }

    public function getAddress(): ?string {
        return $this->address;
    }

    public function setAddress(string $address): self {
        $this->address = $address;

        return $this;
    }

    public function getPhone(): ?string {
        return $this->phone;
    }

    public function setPhone(string $phone): self {
        $this->phone = $phone;

        return $this;
    }

    public function getPasswordReset(): ?string {
        return $this->password_reset;
    }

    public function setPasswordReset(?string $password_reset): self {
        $this->password_reset = $password_reset;

        return $this;
    }

    public function getConfirmed() {
        return $this->confirmed;
    }

    public function setConfirmed($confirmed): self {
        $this->confirmed = $confirmed;

        return $this;
    }
}
