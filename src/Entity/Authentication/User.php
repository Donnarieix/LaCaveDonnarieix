<?php

namespace App\Entity\Authentication;

use App\Entity\File;
use App\Entity\Folder;
use App\Entity\StorageRequest;
use App\Repository\Authentication\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\UniqueConstraint(name: 'UNIQ_IDENTIFIER_EMAIL', fields: ['email'])]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 180)]
    private ?string $email = null;

    #[ORM\Column(length: 16)]
    private ?string $username = null;

    /**
     * @var list<string> The user roles
     */
    #[ORM\Column]
    private array $roles = [];

    /**
     * @var string The hashed password
     */
    #[ORM\Column]
    private ?string $password = null;

    /**
     * @var Collection<int, Folder>
     */
    #[ORM\OneToMany(targetEntity: Folder::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $folders;

    /**
     * @var Collection<int, File>
     */
    #[ORM\OneToMany(targetEntity: File::class, mappedBy: 'owner', orphanRemoval: true)]
    private Collection $files;

    #[ORM\Column(type: Types::BIGINT)]
    private ?string $maxStorage = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $firstname = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastname = null;

    #[ORM\Column]
    private ?int $visibility = 0;

    /**
     * @var Collection<int, StorageRequest>
     */
    #[ORM\OneToMany(targetEntity: StorageRequest::class, mappedBy: 'applicant')]
    private Collection $storageRequests;

    public function __construct()
    {
        $this->folders = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->storageRequests = new ArrayCollection();
    }

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

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    /**
     * A visual identifier that represents this user.
     *
     * @see UserInterface
     */
    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    public function getDisplayName(): string
    {
        $visibility = $this->getVisibility();
        if ($visibility === 0 && empty($this->firstname) && empty($this->lastname)) $visibility = -1;

        return match ($visibility) {
            0 => trim($this->firstname . ' ' . $this->lastname),
            1 => $this->username,
            2 => $this->email,
            default => $this->username,
        };
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

    /**
     * @param list<string> $roles
     */
    public function setRoles(array $roles): static
    {
        $this->roles = $roles;

        return $this;
    }

    /**
     * @see PasswordAuthenticatedUserInterface
     */
    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }

    /**
     * Ensure the session doesn't contain actual password hashes by CRC32C-hashing them, as supported since Symfony 7.3.
     */
    public function __serialize(): array
    {
        $data = (array) $this;
        $data["\0".self::class."\0password"] = hash('crc32c', $this->password);

        return $data;
    }

    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // @deprecated, to be removed when upgrading to Symfony 8
    }

    /**
     * @return Collection<int, Folder>
     */
    public function getFolders(): Collection
    {
        return $this->folders;
    }

    public function addFolder(Folder $folder): static
    {
        if (!$this->folders->contains($folder)) {
            $this->folders->add($folder);
            $folder->setOwner($this);
        }

        return $this;
    }

    public function removeFolder(Folder $folder): static
    {
        if ($this->folders->removeElement($folder)) {
            // set the owning side to null (unless already changed)
            if ($folder->getOwner() === $this) {
                $folder->setOwner(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, File>
     */
    public function getFiles(): Collection
    {
        return $this->files;
    }

    public function addFile(File $file): static
    {
        if (!$this->files->contains($file)) {
            $this->files->add($file);
            $file->setOwner($this);
        }

        return $this;
    }

    public function removeFile(File $file): static
    {
        if ($this->files->removeElement($file)) {
            // set the owning side to null (unless already changed)
            if ($file->getOwner() === $this) {
                $file->setOwner(null);
            }
        }

        return $this;
    }

    public function getMaxStorage(): ?string
    {
        return $this->maxStorage;
    }

    public function setMaxStorage(string $maxStorage): static
    {
        $this->maxStorage = $maxStorage;

        return $this;
    }

    public function getFirstname(): ?string
    {
        return $this->firstname;
    }

    public function setFirstname(?string $firstname): static
    {
        $this->firstname = $firstname;

        return $this;
    }

    public function getLastname(): ?string
    {
        return $this->lastname;
    }

    public function setLastname(?string $lastname): static
    {
        $this->lastname = $lastname;

        return $this;
    }

    public function getVisibility(): ?int
    {
        return $this->visibility;
    }

    public function setVisibility(int $visibility): static
    {
        $this->visibility = $visibility;

        return $this;
    }

    /**
     * @return Collection<int, StorageRequest>
     */
    public function getStorageRequests(): Collection
    {
        return $this->storageRequests;
    }

    public function addStorageRequest(StorageRequest $storageRequest): static
    {
        if (!$this->storageRequests->contains($storageRequest)) {
            $this->storageRequests->add($storageRequest);
            $storageRequest->setApplicant($this);
        }

        return $this;
    }

    public function removeStorageRequest(StorageRequest $storageRequest): static
    {
        if ($this->storageRequests->removeElement($storageRequest)) {
            // set the owning side to null (unless already changed)
            if ($storageRequest->getApplicant() === $this) {
                $storageRequest->setApplicant(null);
            }
        }

        return $this;
    }
}
