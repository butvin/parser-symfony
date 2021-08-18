<?php

namespace App\Service;

use App\Entity\AppStoreApplication;
use App\Entity\AppStoreCategory;
use App\Entity\AppStorePublisher;
use App\Message\AppStoreApplicationDeleteMessage;
use App\Message\AppStoreApplicationUpdateMessage;
use App\Repository\AppStoreCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\Messenger\MessageBusInterface;

class AppStoreApplicationParser
{
    private const WORKDIR = '/application/public/storeIcons';
    private const BASE_URL = 'https://uclient-api.itunes.apple.com/WebObjects/MZStorePlatform.woa/wa/lookup?version=2&caller=webExp&p=lockup&useSsl=true&cc=US&id=%s';
    private const RE_VALIDATION_IDENTIFIER = '';
    private const MAX_RETRY = 5;

    private EntityManagerInterface $em;
    private HttpBrowserProxy $proxyBrowser;
    private MessageBusInterface $bus;
    private AppStoreCategoryRepository $categoryRepository;

    public function __construct(
        MessageBusInterface $bus,
        EntityManagerInterface $em,
        HttpBrowserProxy $proxyBrowser,
        AppStoreCategoryRepository $categoryRepository
    )
    {
        $this->em = $em;
        $this->bus = $bus;
        $this->proxyBrowser = $proxyBrowser;
        $this->categoryRepository = $categoryRepository;
    }

    public function execute(AppStorePublisher $publisher, array $appIds): void
    {
        $this->checkApplications($publisher, $appIds);
    }

    private function getApplicationInfo(string $url, int $retry = 0): ?HttpBrowser
    {
        dump("Getting application info...");
        $browser = $this->proxyBrowser;
        $proxyServer = $browser->getProxy($url);
        $browserRequest = $browser->urlRequest('GET', $url, [], self::RE_VALIDATION_IDENTIFIER, [], $proxyServer);

        if ($browserRequest == null){
            if ($retry < self::MAX_RETRY){
                return $this->getApplicationInfo($url, ++$retry);
            }
            return null;
        }else{
            return $browserRequest;
        }
    }

    private function checkApplications(AppStorePublisher $publisher, array $appIds): void
    {
        $current = [];

        if (!empty($appIds)) {
            $appIds = array_filter($appIds, function($i) { return is_numeric($i); });
            $appIds = urlencode(implode(',', $appIds));

            $url = sprintf(self::BASE_URL, $appIds);

            $current = $this->getApplicationInfo($url)->getResponse()->getContent();
            $current = json_decode($current, true);
            $current = $current['results'];
        }

        $exists = $publisher->getApplicationsForDiff();
        $iconParams = ['{w}' => 128, '{h}' => 128, '{f}' => 'png'];

        //ADD NEW APPS
        foreach (array_diff(array_keys($current), array_keys($exists)) as $externalId) {
            $devises = array_flip($current[$externalId]['deviceFamilies']);
            $isIPadApp = isset($devises['ipad']);
            $isIPhoneApp = isset($devises['iphone']);

            $iconUrl = $current[$externalId]['artwork']['url'];
            $iconUrl = str_replace(array_keys($iconParams), array_values($iconParams), $iconUrl);

            $iconName = uniqid('icon', true) . '.png';
            $iconPath = self::WORKDIR . DIRECTORY_SEPARATOR . $iconName;

            $version = $current[$externalId]['offers'][0]['version']['display'] ?? '';

            $this->iconDownload($iconUrl, $iconPath);

            $categoryId = $current[$externalId]['genres']['0']['genreId'];
            $category = $this->categoryRepository->findOneBy(['externalId' => $categoryId]);

            $newApp = new AppStoreApplication();
            $newApp->setPublisher($publisher);
            $newApp->setExternalId($externalId);
            $newApp->setUrl($current[$externalId]['url']);
            $newApp->setName($current[$externalId]['name']);
            $newApp->setVersion($version);
            $newApp->setIcon($iconName);
            $newApp->setCategory($category);
            $newApp->setIPad($isIPadApp);
            $newApp->setIPhone($isIPhoneApp);
            $newApp->setPurchases($current[$externalId]['hasInAppPurchases'] ?? false);

            $this->em->persist($newApp);
            $this->em->flush();
        }

        dump("exists keys");
        dump(array_keys($exists));
        dump("current keys");
        dump(array_keys($current));
        //UPDATE CURR APPS
        foreach (array_intersect(array_keys($exists), array_keys($current)) as $externalId) {
            dump("CHECK APP - " . $externalId);

            $devises = array_flip($current[$externalId]['deviceFamilies']);
            $isIPadApp = isset($devises['ipad']);
            $isIPhoneApp = isset($devises['iphone']);


            $entity = $exists[$externalId];
            $version = $current[$externalId]['offers'][0]['version']['display'] ?? '';
            $entity->setPublisher($publisher);
            $entity->setUrl($current[$externalId]['url']);
            $entity->setName($current[$externalId]['name']);
            $entity->setIPad($isIPadApp);
            $entity->setIPhone($isIPhoneApp);
            $entity->setPurchases($current[$externalId]['hasInAppPurchases'] ?? false);
            $entity->setDeletedAt(null);

            if ($entity->getVersion() && $entity->getVersion() !== $version) {
                $entity->setUpdatedAt(new \DateTime());
                $this->bus->dispatch(new AppStoreApplicationUpdateMessage($entity->getId(), $entity->getVersion(), $version));
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
            $this->bus->dispatch(new AppStoreApplicationDeleteMessage($publisher->getId(), $deletedAppIds));
        }

        $this->em->flush();
    }

    private function iconDownload(string $iconUrl, string $iconPath, int $retry = 0)
    {
        dump("Icon loading...");
        $browser = $this->proxyBrowser;
        $proxyServer = $browser->getProxy($iconUrl);
        $browserRequest = $browser->makeProxyDownload($iconUrl, $iconPath, $proxyServer);

        if ($browserRequest == null){
            if ($retry < self::MAX_RETRY){
                $this->iconDownload($iconUrl, $iconPath, ++$retry);
            }
        }
    }

    private function isDeletedApplication(AppStoreApplication $application, int $retry = 0): bool
    {
        $url = $application->getUrl();
        $browser = $this->proxyBrowser;
        $proxyServer = $browser->getProxy($url);
        $browserRequest = $browser->urlRequest('POST', $url, [], self::RE_VALIDATION_IDENTIFIER, [], $proxyServer);

        if ($browserRequest == null){
            if ($retry < self::MAX_RETRY){
                return $this->isDeletedApplication($application, ++$retry);
            }else{
                return true;
            }
        }

        return false;
    }
}
