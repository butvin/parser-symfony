<?php

declare(strict_types=1);

namespace App\Twig;

use App\Entity\AppStoreApplication;
use App\Entity\AppStorePublisher;
use App\Repository\AppStorePublisherRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;
use App\Repository\AppStoreApplicationRepository;


class AppStorePublisherApplicationSorter extends AbstractExtension
{
    protected AppStoreApplicationRepository $applicationRepository;
    protected AppStorePublisherRepository $publisherRepository;

    public function __construct(
        AppStoreApplicationRepository $applicationRepository,
        AppStorePublisherRepository $publisherRepository
    )
    {
        $this->applicationRepository = $applicationRepository;
        $this->publisherRepository = $publisherRepository;
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction(
                'app_store_get_sorted_publisher_apps',
                [$this, 'getPublisherApplications']
            ),
            new TwigFunction(
                'app_store_get_ordered_publishers',
                [$this, 'getOrderedPublishers']
            ),
        ];
    }

    public function getPublisherApplications(AppStorePublisher $publisher): ?array
    {
        $applications = $this->applicationRepository->findBy([
                'publisher' => $publisher,
        ]);
        
        if (!$applications) {
            return null;
        }

        $deleted = array_filter($applications, static fn(AppStoreApplication $application) =>
            $application->getDeletedAt() > (new \DateTime(AppStoreApplication::BANNED_TTL))
        );

        $created = array_filter($applications, static fn(AppStoreApplication $application) =>
            $application->getCreatedAt() > (new \DateTime(AppStoreApplication::NEW_TTL))
        );

        $updated = array_filter($applications, static fn(AppStoreApplication $application) =>
            $application->getUpdatedAt() > (new \DateTime(AppStoreApplication::UPDATED_TTL))
        );

        return array_unique(
            array_merge($deleted, $created, $updated, $applications),
            SORT_REGULAR
        );
    }

    final public function getOrderedPublishers(array $publishers)
    {
        $deleted = array_filter($publishers, static fn(AppStorePublisher $publisher) =>
            $publisher->getDeletedAt() < (new \DateTime('-1 day'))
        );

        return array_unique(array_merge($deleted, $publishers), SORT_REGULAR);
    }
}