<?php

namespace App\Entity;

use App\Repository\AppStorePositionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Timestampable\Traits\TimestampableEntity;

/**
 * @ORM\Entity(repositoryClass=AppStorePositionRepository::class)
 * @ORM\Table(
 *     options={"collate"="utf8mb4_bin", "charset"="utf8mb4"},
 *     indexes={@ORM\Index(columns={"country"})})
 *
 */
class AppStorePosition
{
    use TimestampableEntity;

    public const TOP_CAT_ALL_APPS = 'top_category_all';
    public const TOP_IPAD_ALL_APPS_URL = 'top_ipad_all';
    public const TOP_IPAD_PAID_APPS_URL = 'top_ipad_paid';
    public const TOP_IPAD_FREE_APPS_URL = 'top_ipad_free';
    public const TOP_IPHONE_ALL_APPS_URL = 'top_iphone_all';
    public const TOP_IPHONE_PAID_APPS_URL = 'top_iphone_paid';
    public const TOP_IPHONE_FREE_APPS_URL = 'top_iphone_free';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private int $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private string $ratingType;

    /**
     * @ORM\Column(type="string", length=31)
     */
    private string $country;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $prevIndex = null;

    /**
     * @ORM\Column(type="integer", nullable=true)
     */
    private ?int $currIndex = null;

    /**
     * @ORM\Column(type="integer")
     */
    private ?int $totalQuantity = null;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $reason = null;

    /**
     * @ORM\ManyToOne(targetEntity=AppStoreApplication::class, inversedBy="storePositions")
     * @ORM\JoinColumn(nullable=false)
     */
    private AppStoreApplication $application;

    /**
     * @ORM\ManyToOne(targetEntity=AppStoreCategory::class, inversedBy="storePositions")
     * @ORM\JoinColumn(name="category_id", referencedColumnName="id", nullable=true)
     */
    private ?AppStoreCategory $category;

    public function __construct(AppStoreApplication $application)
    {
        $this->application = $application;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getRatingType(): ?string
    {
        return $this->ratingType;
    }

    public function setRatingType(string $ratingType): self
    {
        $this->ratingType = $ratingType;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getPrevIndex(): ?int
    {
        return $this->prevIndex;
    }

    public function setPrevIndex(?int $prevIndex): self
    {
        $this->prevIndex = $prevIndex;

        return $this;
    }

    public function getCurrIndex(): ?int
    {
        return $this->currIndex;
    }

    public function setCurrIndex(?int $currIndex): self
    {
        $this->currIndex = $currIndex;

        return $this;
    }

    public function getTotalQuantity(): ?int
    {
        return $this->totalQuantity;
    }

    public function setTotalQuantity(?int $totalQuantity): self
    {
        $this->totalQuantity = $totalQuantity;

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

    public function getApplication(): AppStoreApplication
    {
        return $this->application;
    }

    public function setApplication(AppStoreApplication $application): self
    {
        $this->application = $application;

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
}
