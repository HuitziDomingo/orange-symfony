<?php

namespace App\Entity;

use App\Repository\MembershipRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=MembershipRepository::class)
 */
class Membership
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
    private $name;

    /**
     * @ORM\Column(type="string", length=500, nullable=true)
     */
    private $description;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $periodicity;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $price;

    /**
     * @ORM\OneToMany(targetEntity=UserMembership::class, mappedBy="membership")
     */
    private $userMemberships;

    public function __construct()
    {
        $this->userMemberships = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getPeriodicity(): ?string
    {
        return $this->periodicity;
    }

    public function setPeriodicity(?string $periodicity): self
    {
        $this->periodicity = $periodicity;

        return $this;
    }

    public function getPrice(): ?string
    {
        return $this->price;
    }

    public function setPrice(?string $price): self
    {
        $this->price = $price;

        return $this;
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
            $userMembership->setMembership($this);
        }

        return $this;
    }

    public function removeUserMembership(UserMembership $userMembership): self
    {
        if ($this->userMemberships->removeElement($userMembership)) {
            // set the owning side to null (unless already changed)
            if ($userMembership->getMembership() === $this) {
                $userMembership->setMembership(null);
            }
        }

        return $this;
    }
}
