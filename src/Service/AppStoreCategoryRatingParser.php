<?php

namespace App\Service;

use App\Entity\AppStoreCategory;
use App\Entity\AppStorePosition;
use Symfony\Component\BrowserKit\HttpBrowser;

class AppStoreCategoryRatingParser
{
    private const BASE_URL = 'https://apps.apple.com/%s/genre/id%s';
    private const RE_VALIDATION_IDENTIFIER = '';
    private const MAX_RETRY = 5;

    private HttpBrowserProxy $proxyBrowser;
    private AppStorePositionCreator $positionCreator;

    public function __construct(
        HttpBrowserProxy $proxyBrowser,
        AppStorePositionCreator $positionCreator
    )
    {
        $this->proxyBrowser = $proxyBrowser;
        $this->positionCreator = $positionCreator;
    }

    public function execute(string $countryCode, AppStoreCategory $category): void
    {
        $this->getCategoryRatingInfo($countryCode, $category);
    }

    private function getCategoryRatingInfo(string $countryCode, AppStoreCategory $category, int $retry = 0, string $proxyServer = null): void
    {
        dump("Category rating parser");
        $url = sprintf(self::BASE_URL, $countryCode, $category->getExternalId());
        $browser = $this->proxyBrowser;
        $browserRequest = $browser->urlRequest('GET', $url, [], self::RE_VALIDATION_IDENTIFIER, [], $proxyServer);

        if ($browserRequest == null) {
            if ($retry < self::MAX_RETRY) {
                $proxyServer = $browser->getProxy($url);
                $this->getCategoryRatingInfo($countryCode, $category, ++$retry, $proxyServer);
            }
        }else{
            $this->checkCategoryRating($category, $countryCode, $browserRequest);
        }
    }

    private function checkCategoryRating(AppStoreCategory $category, string $countryCode, ?HttpBrowser $browserRequest): void
    {
        $categoryApps = $browserRequest->getCrawler()
            ->filter('div[id="selectedcontent"]')
            ->filter('a')->each(function ($node, $i) {
                return $node->attr('href');
            });

        $appIds = [];
        $position = 0;
        foreach ($categoryApps as $application){
            if (empty($application)) {
                continue;
            }

            $appId = AppStorePublisherParser::getIdByUrl($application);
            $appIds[] = $appId;
        }

        $this->positionCreator->create(AppStorePosition::TOP_CAT_ALL_APPS, $appIds, $countryCode, $category);
    }
}
