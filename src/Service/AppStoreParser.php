<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Publisher;
use App\Message\ApplicationDeleteMessage;
use App\Message\ApplicationUpdateMessage;
use App\Message\PublisherDeleteMessage;

class AppStoreParser extends AbstractParser
{
    private const RE_SUPPORTS = '/^https\:\/\/apps\.apple\.com\/\w+\/developer\/[A-z0-9-_]+\/id(?<id>[\d]+)/i';

    private const APPLICATIONS_INFO_URL = 'https://uclient-api.itunes.apple.com/WebObjects/MZStorePlatform.woa/wa/lookup?version=2&caller=webExp&p=lockup&useSsl=true&cc=US&id=%s';

    public function getType(): string
    {
        return Publisher::TYPE_APP_STORE;
    }

    public function getExternalId(string $url): ?string
    {
        return preg_match(self::RE_SUPPORTS, $url, $matches) ?
            urlencode($matches['id']) : null;
    }

    public function execute(Publisher $publisher, int $retry = 0): void
    {
        try {
            $browser = $this->request($publisher->getUrl());

            $data = $browser->getCrawler()->filter('script#shoebox-ember-data-store')->text();
            $data = json_decode($data, true)[$this->getPublisherId($publisher)];
        } catch (\LogicException $exception) {
            if ($retry < self::MAX_RETRY) {
                $this->proxies->next();
                $this->execute($publisher, $retry + 1);
                return;
            }

            $publisher->setReason($exception->getMessage());

            if (null === $publisher->getDeletedAt() && $publisher->getApplications()->count()) {
                $this->bus->dispatch(new PublisherDeleteMessage($publisher->getId(), $publisher->getReason()));
            }

            $publisher->setDeletedAt($publisher->getDeletedAt() ?? new \DateTime());

            foreach ($publisher->getApplications() as $application) {
                $application->setReason($application->getReason() ?? 'Publisher deleted');
                $application->setDeletedAt($application->getDeletedAt() ?? new \DateTime());
            }

            return;
        }

        $this->updatePublisher($publisher, $data);
        $this->updateApplications($publisher, $data);
    }

    protected function getReValidation(): string
    {
        return '/apple\.com/i';
    }

    private function updatePublisher(Publisher $publisher, array $data): void
    {
        $publisher->setExternalId($this->getExternalId($publisher->getUrl()));
        $publisher->setName($data['data']['attributes']['name']);
        $publisher->setUpdatedAt(new \DateTime());
        $publisher->setReason(null);
        $publisher->setDeletedAt(null);
    }

    private function updateApplications(Publisher $publisher, array $data): void
    {
        $appIds = array_unique(array_column($data['included'], 'id'));
        $current = [];

        if (!empty($appIds)) {
            $appIds = array_filter($appIds, function($i) { return is_numeric($i); });
            $appIds = urlencode(implode(',', $appIds));

            $url = sprintf(self::APPLICATIONS_INFO_URL, $appIds);

            $current = $this->request($url)->getResponse();
            $current = json_decode($current->getContent(), true);
            $current = $current['results'];
        }

        $exists = $publisher->getApplicationsForDiff();
        $iconParams = ['{w}' => 128, '{h}' => 128, '{f}' => 'png'];

        foreach (array_diff(array_keys($current), array_keys($exists)) as $externalId) {
            $iconUrl = $current[$externalId]['artwork']['url'];
            $iconUrl = str_replace(array_keys($iconParams), array_values($iconParams), $iconUrl);

            $iconName = uniqid('icon', true) . '.png';
            $iconPath = self::WORKDIR . DIRECTORY_SEPARATOR . $iconName;

            $version = $current[$externalId]['offers'][0]['version']['display'] ?? '';

            $this->download($iconUrl, $iconPath);

            $entity = new Application($publisher, null);
            $entity->setExternalId($externalId);
            $entity->setUrl($current[$externalId]['url']);
            $entity->setName($current[$externalId]['name']);
            $entity->setVersion($version);
            $entity->setIcon($iconName);
            $entity->setPurchases($current[$externalId]['hasInAppPurchases'] ?? false);

            $this->em->persist($entity);
        }

        foreach (array_intersect(array_keys($exists), array_keys($current)) as $externalId) {
            $entity = $exists[$externalId];
            $version = $current[$externalId]['offers'][0]['version']['display'] ?? '';
            $entity->setUrl($current[$externalId]['url']);
            $entity->setName($current[$externalId]['name']);
            $entity->setPurchases($current[$externalId]['hasInAppPurchases'] ?? false);
            $entity->setDeletedAt(null);

            if ($entity->getVersion() && $entity->getVersion() !== $version) {
                $entity->setUpdatedAt(new \DateTime());
                $this->bus->dispatch(new ApplicationUpdateMessage($entity->getId(), $entity->getVersion(), $version));
            }

            $entity->setVersion($version);
        }

        $deletedAppIds = [];
        foreach (array_diff(array_keys($exists), array_keys($current)) as $externalId) {
            $entity = $exists[$externalId];
            if ($this->isDeletedApplication($entity)) {
                $entity->setReason('Not found application in publisher list');

                if (null === $entity->getDeletedAt()) {
                    $deletedAppIds[] = $entity->getId();
                    $entity->setDeletedAt(new \DateTime());
                }
            }
        }

        if (count($deletedAppIds)) {
            $this->bus->dispatch(new ApplicationDeleteMessage($publisher->getId(), $deletedAppIds));
        }
    }

    private function isDeletedApplication(Application $application): bool
    {
        try {
            $this->proxies->next();
            $this->request($application->getUrl());

            return false;
        } catch (\LogicException $exception) {
            return true;
        }
    }

    private function getPublisherId(Publisher $publisher): int
    {
        preg_match(self::RE_SUPPORTS, $publisher->getUrl(), $matches);

        return (int) $matches['id'];
    }
}
