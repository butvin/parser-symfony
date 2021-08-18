<?php

namespace App\Entity;

use App\Repository\AppStoreApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=AppStoreApplicationRepository::class)
 * @ORM\Table(
 *     options={"collate"="utf8mb4_bin", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(columns={"external_id", "name", "publisher_id", "category_id"})})
 *
 */
class AppStoreApplication
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    public const NEW_TTL = '-24 hour';
    public const UPDATED_TTL = '-24 hour';
    public const BANNED_TTL = '-24 hour';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=AppStorePublisher::class, inversedBy="storeApplications")
     */
    private AppStorePublisher $publisher;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private string $externalId;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\Column(type="text", length=2047)
     */
    private string $url;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $icon;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $version;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $iPhone = false;

    /**
     * @ORM\Column(type="boolean", nullable=false)
     */
    private bool $iPad = false;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $reason;

    /**
     * @ORM\ManyToOne(targetEntity=AppStoreCategory::class, inversedBy="storeApplications")
     * @ORM\JoinColumn(nullable=false)
     */
    private ?AppStoreCategory $category;

    /**
     * @ORM\OneToMany(targetEntity=AppStorePosition::class, mappedBy="application")
     */
    private Collection $storePositions;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $purchases;

    public function __construct(?AppStoreCategory $category = null)
    {
        $this->category = $category;
        $this->storePositions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPublisher(): AppStorePublisher
    {
        return $this->publisher;
    }

    public function setPublisher(AppStorePublisher $publisher): self
    {
        $this->publisher = $publisher;

        return $this;
    }

    public function getExternalId(): string
    {
        return $this->externalId;
    }

    public function setExternalId(string $externalId): self
    {
        $this->externalId = $externalId;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getIcon(): ?string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }


    public function getIPhone(): bool
    {
        return $this->iPhone;
    }

    public function setIPhone(bool $iPhone): self
    {
        $this->iPhone = $iPhone;

        return $this;
    }

    public function getIPad(): bool
    {
        return $this->iPad;
    }

    public function setIPad(bool $iPad): self
    {
        $this->iPad = $iPad;

        return $this;
    }

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getCategory(): ?AppStoreCategory
    {
        return $this->category;
    }

    public function setCategory(?AppStoreCategory $category): self
    {
        $this->category = $category;

        return $this;
    }

    /**
     * @return Collection|AppStorePosition[]
     */
    public function getStorePositions(): Collection
    {
        return $this->storePositions;
    }

    public function addStorePosition(AppStorePosition $storePosition): self
    {
        if (!$this->storePositions->contains($storePosition)) {
            $this->storePositions[] = $storePosition;
            $storePosition->setApplication($this);
        }

        return $this;
    }

    public function removeStorePosition(AppStorePosition $storePosition): self
    {
        if ($this->storePositions->removeElement($storePosition)) {
            // set the owning side to null (unless already changed)
            if ($storePosition->getApplication() === $this) {
                $storePosition->setApplication(null);
            }
        }

        return $this;
    }

    public function getPurchases(): ?bool
    {
        return $this->purchases;
    }

    public function setPurchases(bool $purchases): self
    {
        $this->purchases = $purchases;

        return $this;
    }

    public function isBanned(): bool
    {
        return $this->getDeletedAt() > new \DateTime(self::BANNED_TTL);
    }

    public function isNew(): bool
    {
        return $this->getDeletedAt() === null
            && $this->getCreatedAt() > new \DateTime(self::NEW_TTL);
    }

    public function isUpdated(): bool
    {
        return $this->getDeletedAt() === null
            && $this->getUpdatedAt() > new \DateTime(self::UPDATED_TTL);
    }
}
