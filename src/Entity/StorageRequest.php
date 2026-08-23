<?php

namespace App\Entity;

use App\Entity\Authentication\User;
use App\Repository\StorageRequestRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StorageRequestRepository::class)]
class StorageRequest
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'storageRequests')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $applicant = null;

    #[ORM\Column]
    private ?int $requestedValue = null;

    #[ORM\Column(length: 255)]
    private ?string $requestedUnit = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getApplicant(): ?User
    {
        return $this->applicant;
    }

    public function setApplicant(?User $applicant): static
    {
        $this->applicant = $applicant;

        return $this;
    }

    public function getRequestedValue(): ?int
    {
        return $this->requestedValue;
    }

    public function setRequestedValue(int $requestedValue): static
    {
        $this->requestedValue = $requestedValue;

        return $this;
    }

    public function getRequestedUnit(): ?string
    {
        return $this->requestedUnit;
    }

    public function setRequestedUnit(string $requestedUnit): static
    {
        $this->requestedUnit = $requestedUnit;

        return $this;
    }
}
