<?php

namespace App\Entity;

use App\Repository\AppStorePublisherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use App\Validator as AcmeAssert;

/**
 * @AcmeAssert\ContainsAppStoreParseUrl
 *
 * @ORM\Entity(repositoryClass=AppStorePublisherRepository::class)
 * @ORM\Table(
 *     options={"collate"="utf8mb4_bin", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(columns={"external_id", "name"})})
 *
 */
class AppStorePublisher
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private string $externalId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $url;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $address;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private ?string $number = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $reason = null;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="storePublishers")
     */
    private User $user;

    /**
     * @ORM\OneToMany(targetEntity=AppStoreApplication::class, mappedBy="publisher")
     */
    private Collection $storeApplications;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->storeApplications = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
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

    public function setName(?string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

        return $this;
    }

    public function getNumber(): ?string
    {
        return $this->number;
    }

    public function setNumber(?string $number): self
    {
        $this->number = $number;

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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection|AppStoreApplication[]
     */
    public function getStoreApplications(): Collection
    {
        return $this->storeApplications;
    }

    public function addStoreApplication(AppStoreApplication $storeApplication): self
    {
        if (!$this->storeApplications->contains($storeApplication)) {
            $this->storeApplications[] = $storeApplication;
            $storeApplication->setPublisher($this);
        }

        return $this;
    }

    public function removeStoreApplication(AppStoreApplication $storeApplication): self
    {
        if ($this->storeApplications->removeElement($storeApplication)) {
            // set the owning side to null (unless already changed)
            if ($storeApplication->getPublisher() === $this) {
                $storeApplication->setPublisher(null);
            }
        }

        return $this;
    }

    /**
     * @return AppStoreApplication[]
     */
    public function getApplicationsForDiff(): array
    {
        $result = [];

        foreach ($this->getStoreApplications() as $application) {
            $result[$application->getExternalId()] = $application;
        }

        return $result;
    }

    public function getNewApplications(): Collection
    {
        $result = [];

        foreach ($this->getStoreApplications() as $application) {
            if ($application->isNew()) {
                $result[] = $application;
            }
        }

        return new ArrayCollection($result);
    }

    public function getUpdatedApplications(): Collection
    {
        $result = [];

        foreach ($this->getStoreApplications() as $application) {
            if ($application->isUpdated()) {
                $result[] = $application;
            }
        }

        return new ArrayCollection($result);
    }

    public function getBannedApplications(): Collection
    {
        $result = [];

        foreach ($this->getStoreApplications() as $application) {
            if ($application->isBanned()) {
                $result[] = $application;
            }
        }

        return new ArrayCollection($result);
    }

    public function getSortingScore(): int
    {
        if (null === $this->getName()) {
            return 0;
        }

        $weight = 2;
        $weight += (1 + $this->getBannedApplications()->count()) ** 4;
        $weight += (1 + $this->getNewApplications()->count()) ** 3;
        $weight += (1 + $this->getUpdatedApplications()->count()) ** 2;

        return $this->getCreatedAt()->getTimestamp() * $weight;
    }
}
