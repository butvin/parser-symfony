<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PositionRepository;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Symfony\Component\Serializer\Annotation\Groups;

/**
 * @ORM\Entity(repositoryClass=PositionRepository::class)
 */
class Position
{
    public const RATING_TYPE_MAIN_LIST = 'main_list';
    public const RATING_TYPE_MAIN_TOP = 'main_top';
    public const RATING_TYPE_MAIN_NEW = 'main_new';
    public const RATING_TYPE_MAIN_FREE = 'main_free';
    public const RATING_TYPE_MAIN_PAID = 'main_paid';
    public const RATING_TYPE_CATEGORY_LIST = 'cat_list';
    public const RATING_TYPE_CATEGORY_TOP = 'cat_top';
    public const RATING_TYPE_CATEGORY_NEW = 'cat_new';

    public const RATING_TYPES = [
        self::RATING_TYPE_CATEGORY_LIST => 'General',
        self::RATING_TYPE_CATEGORY_TOP => 'Top',
        self::RATING_TYPE_CATEGORY_NEW => 'New',
        self::RATING_TYPE_MAIN_LIST => 'List main',
        self::RATING_TYPE_MAIN_TOP => 'Top main',
        self::RATING_TYPE_MAIN_NEW => 'New main',
        self::RATING_TYPE_MAIN_FREE => 'Free main',
        self::RATING_TYPE_MAIN_PAID => 'Paid main',
    ];

    /**
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ORM\Column(type="integer")
     * @Groups({"jsonResponse", "api"})
     */
    private int $id;

    /**
     * @ORM\Column(name="`index`", type="integer", nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private ?int $index = null;

    /**
     * @ORM\Column(name="`prev_index`", type="integer", nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private ?int $prevIndex = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private ?string $ratingType = null;

    /**
     * @ORM\Column(type="string", nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private ?int $totalQuantity = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private ?string $reason = null;

    /**
     * @ORM\ManyToOne(targetEntity=Application::class)
     * @ORM\JoinColumn(nullable=false)
     * @Groups({"jsonResponse", "api"})
     */
    private Application $application;

    /**
     * @ORM\ManyToOne(targetEntity=Category::class, inversedBy="position")
     * @ORM\JoinColumn(
     *     name="category_id",
     *     referencedColumnName="id",
     *     nullable=false
     *     )
     * @Groups({"jsonResponse", "api"})
     */
    private Category $category;

    /**
     * @ORM\Column(type="string", length=31, nullable=false)
     * @Groups({"jsonResponse", "api"})
     */
    private string $locale;

    /**
     * @ORM\Column(type="string", length=31, nullable=false)
     * @Groups({"jsonResponse", "api"})
     */
    private string $country;

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

    public function __construct(Application $application, Category $category)
    {
        $this->application = $application;
        $this->category = $category;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getIndex(): ?int
    {
        return $this->index;
    }

    public function setIndex(?int $index): self
    {
        $this->index = $index;

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

    public function getReason(): ?string
    {
        return $this->reason;
    }

    public function setReason(?string $reason): self
    {
        $this->reason = $reason;

        return $this;
    }

    public function getApplication(): Application
    {
        return $this->application;
    }

    public function setApplication(Application $application): self
    {
        $this->application = $application;

        return $this;
    }

    public function getCategory(): Category
    {
        return $this->category;
    }

    public function setCategory(Category $category): self
    {
        $this->category = $category;

        return $this;
    }

    public function getLocale(): string
    {
        return $this->locale;
    }

    public function setLocale(string $locale): self
    {
        $this->locale = $locale;

        return $this;
    }

    public function getCountry(): string
    {
        return $this->country;
    }

    public function setCountry(string $country): self
    {
        $this->country = $country;

        return $this;
    }

    public function getRatingType(): ?string
    {
        return $this->ratingType;
    }

    public function setRatingType(?string $ratingType): self
    {
        $this->ratingType = $ratingType;

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

    public function getCreatedAt(): \DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTime
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTime $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }
}
