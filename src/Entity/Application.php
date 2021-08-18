<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Doctrine\ORM\Mapping\UniqueConstraint;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=ApplicationRepository::class)
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(columns={"publisher_id", "external_id"})})
 */
class Application
{
    use SoftDeleteableEntity;

    public const WORKDIR = 'public/icons/';
    public const DEFAULT_LOCALE = 'en_US';
    public const DEFAULT_COUNTRY = 'US';
    public const NEW_TTL = '-24 hour';
    public const UPDATED_TTL = '-24 hour';
    public const BANNED_TTL = '-24 hour';
    private const URL_PATTERN = 'https://play.google.com/store/apps/details?id=%s';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"jsonResponse", "api"})
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=Publisher::class, inversedBy="applications", cascade={"remove"})
     * @ORM\JoinColumn(nullable=false, onDelete="CASCADE")
     * @Groups({"jsonResponse", "api"})
     */
    private Publisher $publisher;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"jsonResponse", "api"})
     */
    private string $externalId;

    /**
     * @ORM\Column(type="string", length=2047)
     * @Groups({"jsonResponse", "api"})
     */
    private string $url;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"jsonResponse", "api"})
     */
    private string $icon;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"jsonResponse", "api"})
     */
    private string $name;

    /**
     * @ORM\Column(type="string", length=127)
     * @Groups({"jsonResponse", "api"})
     */
    private string $version;

    /**
     * @ORM\Column(type="boolean")
     * @Groups({"jsonResponse", "api"})
     */
    private bool $purchases;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private ?string $reason = null;

    /**
     * @Gedmo\Timestampable(on="create")
     * @ORM\Column(type="datetime")
     * @Groups({"jsonResponse", "api"})
     */
    protected \DateTime $createdAt;

    /**
     * @ORM\Column(type="datetime", nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    protected ?\DateTime $updatedAt;

    /**
     * @ORM\OneToMany(
     *     targetEntity=Position::class,
     *     mappedBy="application",
     *     orphanRemoval=true
     * )
     */
    private Collection $positions;

    /**
     * @ORM\ManyToOne(
     *     targetEntity=Category::class,
     *     inversedBy="apploication"
     * )
     * @ORM\JoinColumn(
     *     name="category_id",
     *     referencedColumnName="id",
     *     nullable=true
     * )
     * @Groups({"jsonResponse", "api"})
     */
    private ?Category $category;

    public function __construct(Publisher $publisher, ?Category $category = null)
    {
        $this->publisher = $publisher;
        if ($publisher->getType() === Publisher::TYPE_APP_STORE) {
            $this->category = null;
        } else {
            $this->category = $category;
        }

        $this->positions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getPublisher(): Publisher
    {
        return $this->publisher;
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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function setIcon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getPurchases(): bool
    {
        return $this->purchases;
    }

    public function setPurchases(bool $purchases): self
    {
        $this->purchases = $purchases;

        return $this;
    }

    public function getCategory(): ?Category
    {
        return $this->category;
    }

    public function setCategory(?Category $category): self
    {
        $this->category = $category;

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

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
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

    public function isBanned(): bool
    {
        return $this->getDeletedAt() > new \DateTime(self::BANNED_TTL);
    }

    public function getSortingScore(): int
    {
        $score = 2;

        if ($this->isBanned()) {
            $score **= 4;
        }

        if ($this->isNew()) {
            $score **= 3;
        }

        if ($this->isUpdated()) {
            $score **= 2;
        }

        return $score;
    }

    /**
     * @return Collection|Position[]
     */
    public function getPositions(): Collection
    {
        return $this->positions;
    }

    public function removePosition(Position $position): self
    {
        if ($this->positions->contains($position)) {
            $this->positions->removeElement($position);
        }

        return $this;
    }

    public function addPosition(?Position $position): self
    {
        if (!$this->positions->contains($position)) {
            $this->positions[] = $position;
        }

        return $this;
    }
}
