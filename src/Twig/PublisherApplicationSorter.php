<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\Application;
use App\Entity\Publisher;
use App\Repository\PublisherRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Repository\ApplicationRepository;


class PublisherApplicationSorter extends AbstractExtension
{
    protected ApplicationRepository $applicationRepository;
    protected PublisherRepository $publisherRepository;

    public function __construct(
        ApplicationRepository $applicationRepository,
        PublisherRepository $publisherRepository
    )
    {
        $this->applicationRepository = $applicationRepository;
        $this->publisherRepository = $publisherRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'get_sorted_publisher_apps',
                [$this, 'getPublisherApplications']
            ),
            new TwigFunction(
                'get_ordered_publishers',
                [$this, 'getOrderedPublishers']
            ),
        ];
    }

    public function getPublisherApplications(Publisher $publisher): ?array
    {
        $applications = $this->applicationRepository->findBy([
                'publisher' => $publisher,
        ]);
        
        if (!$applications) {
            return null;
        }

        $deleted = array_filter($applications, static fn(Application $application) =>
            $application->getDeletedAt() > (new \DateTime(Application::BANNED_TTL))
        );

        $created = array_filter($applications, static fn(Application $application) =>
            $application->getCreatedAt() > (new \DateTime(Application::NEW_TTL))
        );

        $updated = array_filter($applications, static fn(Application $application) =>
            $application->getUpdatedAt() > (new \DateTime(Application::UPDATED_TTL))
        );

        return array_unique(
            array_merge($deleted, $created, $updated, $applications),
            SORT_REGULAR
        );
    }

    final public function getOrderedPublishers(array $publishers)
    {
        $deleted = array_filter($publishers, static fn(Publisher $publisher) =>
            $publisher->getDeletedAt() < (new \DateTime('-1 day'))
        );

        return array_unique(array_merge($deleted, $publishers), SORT_REGULAR);
    }
}