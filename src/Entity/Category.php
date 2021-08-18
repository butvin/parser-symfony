<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * Actual Google PlayMarket categories.
 * @link https://data.42matters.com/api/meta/android/apps/top_chart_categories.json
 * @link https://data.42matters.com/api/meta/android/apps/app_categories.json
 *
 * @ORM\Entity(
 *     repositoryClass=CategoryRepository::class,
 *     readOnly=true
 * )
 * @UniqueEntity("externalId")
 */
class Category
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"jsonResponse", "api"})
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=127, nullable=false, unique=true)
     * @Groups({"jsonResponse", "api"})
     */
    private string $externalId;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private string $name;

    public function getId(): int
    {
        return $this->id;
    }

    public function getExternalId(): ?string
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
}
