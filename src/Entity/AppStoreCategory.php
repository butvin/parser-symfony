<?php

namespace App\Entity;

use App\Repository\AppStoreCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=AppStoreCategoryRepository::class)
 * @ORM\Table(
 *     options={"collate"="utf8mb4_bin", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(columns={"external_id", "name"})})
 *
 */
class AppStoreCategory
{
    use TimestampableEntity;

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
     * @ORM\Column(type="string", length=255)
     */
    private string $name;

    /**
     * @ORM\OneToMany(targetEntity=AppStoreApplication::class, mappedBy="category")
     */
    private Collection $storeApplications;

    public function __construct()
    {
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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
            $storeApplication->setCategory($this);
        }

        return $this;
    }

    public function removeStoreApplication(AppStoreApplication $storeApplication): self
    {
        if ($this->storeApplications->removeElement($storeApplication)) {
            // set the owning side to null (unless already changed)
            if ($storeApplication->getCategory() === $this) {
                $storeApplication->setCategory(null);
            }
        }

        return $this;
    }
}
