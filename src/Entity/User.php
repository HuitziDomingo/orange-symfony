<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=UserRepository::class)
 */
class User
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $firstName;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $lastName;

    /**
     * @ORM\Column(type="string", unique=true, nullable=true)
     */
    private $email;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $password;

    /**
     * @ORM\Column(type="json")
     */
    private $roles = [];

    /**
     * @ORM\OneToMany(targetEntity=UserMembership::class, mappedBy="user")
     */
    private $userMemberships;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $customerStripeId;


    public function __construct()
    {
        $this->userMemberships = new ArrayCollection();
    }


    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFirstName(): ?string
    {
        return $this->firstName;
    }

    public function setFirstName(?string $firstName): self
    {
        $this->firstName = $firstName;

        return $this;
    }

    public function getLastName(): ?string
    {
        return $this->lastName;
    }

    public function setLastName(?string $lastName): self
    {
        $this->lastName = $lastName;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): self
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /**
     * @return string
     */
    public function getRole(): string
    {
        $roles = $this->getRoles();

        return $roles[0];
    }

    /**
     * Returns the roles or permissions granted to the user for security.
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantees that a user always has at least one role for security
        if (empty($roles)) {
            $roles[] = 'ROLE_CLIENT';
        }

        return $roles;
    }

    /**
     * @return array
     */
    public function getRoleKey(): array {
        $roleKey = $this->roles;

        $roles = [];
        if (empty($roleKey)) {
            $roles[] = 'ROLE_CLIENT';
        } else {
            foreach ($roleKey as $rs){
                $roles[] = $rs->getRole();
            }
        }

        return $roles;

    }

    public function setRoles(array $roles): void
    {
        $this->roles = $roles;
    }

    /**
     * @return Collection|UserMembership[]
     */
    public function getUserMemberships(): Collection
    {
        return $this->userMemberships;
    }

    public function addUserMembership(UserMembership $userMembership): self
    {
        if (!$this->userMemberships->contains($userMembership)) {
            $this->userMemberships[] = $userMembership;
            $userMembership->setUser($this);
        }

        return $this;
    }

    public function removeUserMembership(UserMembership $userMembership): self
    {
        if ($this->userMemberships->removeElement($userMembership)) {
            // set the owning side to null (unless already changed)
            if ($userMembership->getUser() === $this) {
                $userMembership->setUser(null);
            }
        }

        return $this;
    }

    public function getCustomerStripeId(): ?string
    {
        return $this->customerStripeId;
    }

    public function setCustomerStripeId(?string $customerStripeId): self
    {
        $this->customerStripeId = $customerStripeId;

        return $this;
    }

}
