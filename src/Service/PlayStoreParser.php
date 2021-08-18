<?php

namespace App\Service;

use App\Entity\Application;
use App\Entity\Publisher;
use App\Message\ApplicationDeleteMessage;
use App\Message\ApplicationUpdateMessage;
use App\Message\PublisherDeleteMessage;
use App\Entity\Category;
use Symfony\Component\DomCrawler\Crawler;


class PlayStoreParser extends AbstractParser
{
    private const PUBLISHER_NAME_SELECTORS = ['h1[itemprop="name"]', 'div.xwY9Zc h2.sv0AUd.bs3Xnd'];

    private const APPLICATION_SELECTOR = 'div.mpg5gc';

    private const RE_SUPPORTS = '/^https\:\/\/play\.google\.com\/store\/apps\/dev.*\?id\=(?<id>[\w\d\+\_\-\%]+)\&?/i';

    public function getType(): string
    {
        return Publisher::TYPE_PLAY_STORE;
    }

    public function getExternalId(string $url): ?string
    {
        return preg_match(self::RE_SUPPORTS, $url, $matches) ?
            urlencode($matches['id']) : null;
    }

    public function execute(Publisher $publisher, int $retry = 0): void
    {
        try {
            $url = $publisher->getUrl();
            $url = $this->setLanguage($url);
            $url = $this->setCountry($url);

            $browser = $this->request($url);
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

        $this->updatePublisher($publisher, $browser->getCrawler());
        $this->updateApplications($publisher, $browser->getCrawler());
    }

    protected function getReValidation(): string
    {
        return '/Google LLC/i';
    }

    private function updatePublisher(Publisher $publisher, Crawler $crawler): void
    {
        $publisher->setExternalId($this->getExternalId($publisher->getUrl()));
        $publisher->setUpdatedAt(new \DateTime());
        $publisher->setReason(null);
        $publisher->setDeletedAt(null);

        foreach (self::PUBLISHER_NAME_SELECTORS as $selector) {
            if ($crawler->filter($selector)->count()) {
                $publisher->setName($crawler->filter($selector)->text());
                return;
            }
        }
    }

    private function updateApplications(Publisher $publisher, Crawler $crawler): void
    {
        $additional = $crawler->filter('div.W9yFB a');

        if ($additional->count()) {
            dump('Additional detected!');

            $url = $additional->link()->getUri();
            $url = $this->setLanguage($url);
            $url = $this->setCountry($url);

            $browser = $this->request($url);
            $this->updateApplications($publisher, $browser->getCrawler());

            return;
        }

        $current = [];
        $exists = $publisher->getApplicationsForDiff();

        $crawler
            ->filter(self::APPLICATION_SELECTOR)
            ->each(function (Crawler $node, $i) use (&$current) {
                $url = $node->filter('a')->link()->getUri();
                $url = $this->setLanguage($url);
                $url = $this->setCountry($url);

                $name = $node->filter('div.nnK0zc')->text();
                $icon = $node->filter('img');
                $icon = $icon->attr('src') ?? $icon->attr('data-src');

                $qs = parse_url($url, PHP_URL_QUERY);
                parse_str($qs, $qp);

                $crawler = $this->request($url)->getCrawler();

                $categoryUrl = $crawler->filter("a[itemprop='genre']")->attr('href');
                $exploded = (explode('/', $categoryUrl));
                $categoryExternalId = mb_strtoupper(array_pop($exploded));
                $category = $this->em->getRepository(Category::class)->findOneBy(['externalId' => $categoryExternalId]);

                $version = $crawler->filter('div.IxB2fe .hAyfc')->reduce(function (Crawler $node, $i) {
                    return 'Current Version' === $node->filter('div.BgcNfc')->text();
                });

                $version = $version->filter('div.IQ1z0d .htlgb');

                if (!$version->count()) {
                    throw new \LogicException(sprintf(
                        'Version not found in application "%s" with proxy "%s".',
                        $url,
                        $this->proxies->current()
                    ));
                }

                $version = $version->text();

                $purchases = false;
                if ($crawler->filter('div.bSIuKf')->count()) {
                    $purchases = $crawler->filter('div.bSIuKf')->text();
                    $purchases = preg_match('/Offers in\-app purchases/i', $purchases);
                }

                $current[$qp['id']] = [
                    'id'        => $qp['id'],
                    'url'       => $url,
                    'name'      => $name,
                    'icon'      => $icon,
                    'version'   => $version,
                    'purchases' => $purchases,
                    'category' => $category,
                ];
            })
        ;

        foreach (array_diff(array_keys($current), array_keys($exists)) as $externalId) {
            $iconName = uniqid('icon', true) . '.png';
            $iconPath = self::WORKDIR . DIRECTORY_SEPARATOR . $iconName;
            $version = $current[$externalId]['version'] ?? '';
            $category = ($current[$externalId]['category'] instanceof Category) ? $current[$externalId]['category'] : null;

            $this->download($current[$externalId]['icon'], $iconPath);

            $entity = new Application($publisher, $category);
            $entity->setExternalId($externalId);
            $entity->setUrl($current[$externalId]['url']);
            $entity->setIcon($iconName);
            $entity->setName($current[$externalId]['name']);
            $entity->setPurchases($current[$externalId]['purchases']);
            $entity->setVersion($version);

            $this->em->persist($entity);
        }

        foreach (array_intersect(array_keys($exists), array_keys($current)) as $externalId) {
            $entity = $exists[$externalId];
            $version = $current[$externalId]['version'] ?? '';
            $entity->setUrl($current[$externalId]['url']);
            $entity->setName($current[$externalId]['name']);
            $entity->setPurchases($current[$externalId]['purchases']);
            $entity->setReason(null);
            $entity->setDeletedAt(null);
            $entity->setCategory($current[$externalId]['category']);

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

    private function setLanguage(string $url): string
    {
        if (preg_match('/(?<lang>hl=[\w]+)/i', $url, $matches)) {
            return str_ireplace($matches['lang'], 'hl=en', $url);
        }

        return $url . '&hl=en';
    }

    private function setCountry(string $url): string
    {
        if (preg_match('/(?<country>gl=[\w]+)/i', $url, $matches)) {
            return str_ireplace($matches['country'], 'gl=us', $url);
        }

        return $url . '&gl=us';
    }
}
