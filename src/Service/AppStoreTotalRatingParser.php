<?php

namespace App\Service;

use App\Helper\FormatValidator;
use App\Entity\AppStorePosition;
use Symfony\Component\BrowserKit\HttpBrowser;

class AppStoreTotalRatingParser
{
    private array $totalRatingTypes = [
        AppStorePosition::TOP_IPAD_ALL_APPS_URL    => 'https://itunes.apple.com/WebObjects/MZStore.woa/wa/viewRoom?fcId=1462898148',
        AppStorePosition::TOP_IPAD_PAID_APPS_URL   => 'https://itunes.apple.com/WebObjects/MZStore.woa/wa/viewRoom?fcId=1462898544',
        AppStorePosition::TOP_IPAD_FREE_APPS_URL   => 'https://itunes.apple.com/WebObjects/MZStore.woa/wa/viewRoom?fcId=1462898699',
        AppStorePosition::TOP_IPHONE_ALL_APPS_URL  => 'https://itunes.apple.com/WebObjects/MZStore.woa/wa/viewRoom?fcId=1462897722',
        AppStorePosition::TOP_IPHONE_PAID_APPS_URL => 'https://itunes.apple.com/WebObjects/MZStore.woa/wa/viewRoom?fcId=1462898128',
        AppStorePosition::TOP_IPHONE_FREE_APPS_URL => 'https://itunes.apple.com/WebObjects/MZStore.woa/wa/viewRoom?fcId=1462898137'
    ];

    //private const BASE_URL = 'https://apps.apple.com/WebObjects/MZStore.woa/wa/viewGrouping?cc=ua&id=25129&l=ru';
    private const RE_VALIDATION_IDENTIFIER = "";
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

    public function execute(string $countryCode): void
    {
        foreach ($this->totalRatingTypes as $ratingTypeName => $ratingTypeUrl){
            $this->getTotalRatingInfo($countryCode, $ratingTypeName, $ratingTypeUrl);
        }
    }

    private function getTotalRatingInfo(string $countryCode, string $ratingType, string $ratingUrl, int $retry = 0, string $proxyServer = null)
    {
        dump("Total rating parser");
        $httpClientParam = [
            'headers' => [
                'X-Apple-Store-Front' => '143492-16,32',
                'Cookie' => [
                    'geo' => strtoupper($countryCode)
                ]
            ]
        ];
        $browser = $this->proxyBrowser;
        $browserRequest = $browser->urlRequest('GET', $ratingUrl, [], self::RE_VALIDATION_IDENTIFIER, $httpClientParam, $proxyServer);

        if ($browserRequest == null){
            if ($retry < self::MAX_RETRY){
                $proxyServer = $browser->getProxy($ratingUrl);
                $this->getTotalRatingInfo($countryCode, $ratingType, $ratingUrl, ++$retry, $proxyServer);
            }
        }else{
            $this->checkTotalRating(strtolower($countryCode), $ratingType, $browserRequest);
        }
    }

    private function checkTotalRating(string $countryCode, string $ratingType, ?HttpBrowser $browserRequest): void
    {
        $itemsResponse = $this->getAppsJSON($browserRequest->getResponse()->getContent());

        if (FormatValidator::isJSON($itemsResponse)) {
            $appsInfo = json_decode($itemsResponse);
            $appIds = $appsInfo->pageData->roomPageData->adamIds;

            $this->positionCreator->create($ratingType, $appIds, $countryCode);
        }
    }

    private function getAppsJSON(string $content): string
    {
        $startMarker = 'its.serverData=';
        $startPosition = strpos($content, $startMarker);
        if ($startPosition > 0) {
            $content = substr($content, $startPosition + 15);

            if (!empty($content)) {
                $finishMarker = '</script>';
                $finishPosition = strpos($content, $finishMarker);

                return substr($content, 0, $finishPosition);
            }
        }

        return '';
    }

//    private static function setLocation(string $url, string $location): string
//    {
//        if (preg_match('/(?<location>gl=[\w-]+)/i', $url, $matches)) {
//            return str_ireplace($matches['location'], 'gl=' . $location, $url);
//        }
//
//        return $url . '&gl=' . $location;
//    }
}

/*$queryParamRef = [
    //'id' => '25129',
    //'popId' => '30',
    //'genreId' => '36'
];
$httpClientParam = [
    'headers' => [
        //'User-Agent' => 'iTunes/12.4.3 (Windows; Microsoft Windows 10.0 x64 Home Premium Edition (Build 9200)) AppleWebKit/7601.6016.1000.7',
        'X-Apple-Store-Front' => '143492-16,32',
        //'X-Apple-I-MD-RINFO' => '50660608',
        //'X-Apple-Tz' => '10800',
        //'X-Apple-I-MD' => 'AAAABQAAABBWooPJZemIdzkMDczELLRCAAAAAg==',
        //'X-Apple-I-MD-M' => 'dEnHamxbc00htJqrUij968IbqM7SfeA+SjJzGE9ra0bTtK7dWchjMimVbBLXEe5OeicSiNSRWK8UzuW1',
        //'Cookie' => [
        //'geo=UA',
        //'xp_ab=1#d5VBr6w+-2+loTvusm03',
        //'xp_ci=3z40frPDzEY0z4WlzCe2zCCp3jLua'
        //]
    ]
];*/