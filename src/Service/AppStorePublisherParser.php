<?php

namespace App\Service;

use App\Entity\AppStorePublisher;
use App\Message\AppStorePublisherDeleteMessage;
use App\Message\PublisherDeleteMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\Messenger\MessageBusInterface;

class AppStorePublisherParser
{
    private const RE_VALIDATION_IDENTIFIER = '';
    private const MAX_RETRY = 5;

    private EntityManagerInterface $em;
    private MessageBusInterface $bus;
    private HttpBrowserProxy $proxyBrowser;
    private AppStoreApplicationParser $applicationParser;

    public function __construct(
        EntityManagerInterface $em,
        MessageBusInterface $bus,
        HttpBrowserProxy $proxyBrowser,
        AppStoreApplicationParser $applicationParser
    )
    {
        $this->em = $em;
        $this->bus = $bus;
        $this->proxyBrowser = $proxyBrowser;
        $this->applicationParser = $applicationParser;
    }

    public function execute(AppStorePublisher $publisher): void
    {
       $this->getPublisherInfo($publisher);
    }

    public function getPublisherInfo(AppStorePublisher $publisher, int $retry = 0): void
    {
        $url = $publisher->getUrl();
        $browser = $this->proxyBrowser;
        $proxyServer = $browser->getProxy($url);
        $browserRequest = $browser->urlRequest('GET', $url, [], self::RE_VALIDATION_IDENTIFIER, [], $proxyServer);

        if ($browserRequest == null){
            if ($retry < self::MAX_RETRY){
                $this->getPublisherInfo($publisher, ++$retry);
            }else{
                $publisher->setReason("Publisher not found by URL");

                if (null === $publisher->getDeletedAt() && $publisher->getStoreApplications()->count()) {
                    $this->bus->dispatch(new AppStorePublisherDeleteMessage($publisher->getId(), $publisher->getReason()));
                }

                $publisher->setDeletedAt($publisher->getDeletedAt() ?? new \DateTime());

                foreach ($publisher->getStoreApplications() as $application) {
                    $application->setReason($application->getReason() ?? 'Publisher deleted');
                    $application->setDeletedAt($application->getDeletedAt() ?? new \DateTime());
                }
            }
        }else{
            $this->checkPublisher($publisher, $browserRequest);
        }
    }

    private function checkPublisher(AppStorePublisher $publisher, ?HttpBrowser $browserRequest)
    {
        $publisherName = $browserRequest->getCrawler()
            ->filter('h1.page-header__title')
            ->text();

        $publisher->setExternalId(self::getIdByUrl($publisher->getUrl()));
        $publisher->setUpdatedAt(new \DateTime());
        $publisher->setReason(null);
        $publisher->setDeletedAt(null);
        $publisher->setName($publisherName);

        $this->em->persist($publisher);
        $this->em->flush();

        $this->checkApplications($publisher, $browserRequest);
    }

    private function checkApplications(AppStorePublisher $publisher, HttpBrowser $browserRequest): void
    {
        $data = $browserRequest->getCrawler()->filter('script#shoebox-ember-data-store')->text();
        $data = json_decode($data, true)[$publisher->getExternalId()];

        $appIds = array_unique(array_column($data['included'], 'id'));

        //Update apps info
        $this->applicationParser->execute($publisher, $appIds);
    }

    public static function getIdByUrl(string $publisherUrl): int
    {
        $array = explode('/', $publisherUrl);

        $categoryId = end($array);
        $categoryId = str_replace('id', '', $categoryId);

        return (int) $categoryId;
    }

    public static function isDeveloperUrl(string $publisherUrl): bool
    {
        if (stripos($publisherUrl, '/developer/') == false){
            return false;
        }

        return true;
    }
}
