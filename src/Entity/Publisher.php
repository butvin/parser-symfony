<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use App\Repository\PublisherRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Gedmo\SoftDeleteable\Traits\SoftDeleteableEntity;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Doctrine\ORM\Mapping\UniqueConstraint;
use App\Validator as AcmeAssert;


/**
 * @AcmeAssert\ContainsParseUrl
 *
 * @ORM\Entity(repositoryClass=PublisherRepository::class)
 * @ORM\Table(uniqueConstraints={@UniqueConstraint(columns={"external_id"})})
 * @UniqueEntity("externalId")
 */
class Publisher
{
    use TimestampableEntity;
    use SoftDeleteableEntity;

    public const TYPE_PLAY_STORE = 'PlayStore';
    public const TYPE_APP_STORE = 'AppStore';

    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"jsonResponse", "api"})
     */
    private int $id;

    /**
     * @ORM\ManyToOne(targetEntity=User::class)
     * @ORM\JoinColumn(nullable=false)
     */
    private User $user;

    /**
     * @ORM\Column(type="string", length=75)
     * @Groups({"jsonResponse", "api"})
     */
    private string $type;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private string $externalId;

    /**
     * @Assert\NotBlank
     *
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private ?string $address;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     */
    private ?string $number = null;

    /**
     * @Assert\NotBlank
     * @Assert\Url
     * @ORM\Column(type="string", length=1024)
     * @Groups({"jsonResponse", "api"})
     */
    private string $url;

    /**
     * @ORM\Column(type="string", length=1024, nullable=true)
     * @Groups({"jsonResponse", "api"})
     */
    private ?string $name = null;

    /**
     * @ORM\Column(type="text", nullable=true)
     */
    private ?string $reason = null;

    /**
     * @ORM\OneToMany(targetEntity=Application::class, mappedBy="publisher", orphanRemoval=true)
     */
    private Collection $applications;

    public function __construct(User $user)
    {
        $this->user = $user;
        $this->applications = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

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

    public function getAddress(): ?string
    {
        return $this->address;
    }

    public function setAddress(?string $address): self
    {
        $this->address = $address;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

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

    /**
     * @return Collection|Application[]
     */
    public function getApplications(): Collection
    {
        return $this->applications;
    }

    /**
     * @return Application[]
     */
    public function getApplicationsForDiff(): array
    {
        $result = [];

        foreach ($this->getApplications() as $application) {
            $result[$application->getExternalId()] = $application;
        }

        return $result;
    }

    public function getApplicationsSorted(): Collection
    {
        $result = $this->getApplications()->toArray();

        uasort($result, static function (Application $first, Application $second) {
            if ($first->getSortingScore() === $second->getSortingScore()) {
                return 0;
            }

            return $first->getSortingScore() > $second->getSortingScore() ? -1 : 1;
        });

        return new ArrayCollection($result);
    }

    public function getNewApplications(): Collection
    {
        $result = [];

        foreach ($this->getApplications() as $application) {
            if ($application->isNew()) {
                $result[] = $application;
            }
        }

        return new ArrayCollection($result);
    }

    public function getUpdatedApplications(): Collection
    {
        $result = [];

        foreach ($this->getApplications() as $application) {
            if ($application->isUpdated()) {
                $result[] = $application;
            }
        }

        return new ArrayCollection($result);
    }

    public function getBannedApplications(): Collection
    {
        $result = [];

        foreach ($this->getApplications() as $application) {
            if ($application->isBanned()) {
                $result[] = $application;
            }
        }

        return new ArrayCollection($result);
    }

    public function addApplication(Application $application): self
    {
        if (!$this->applications->contains($application)) {
            $this->applications[] = $application;
        }

        return $this;
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
