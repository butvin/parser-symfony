<?php

namespace App\Service;

use App\Entity\AppStoreCategory;
use App\Repository\AppStoreCategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\BrowserKit\HttpBrowser;

class AppStoreCategoryParser
{
    //АДРЕС БЕЗ СУБКАТЕГОРИЙ
    private const BASE_URL = 'https://apps.apple.com/%s/genre/id6000';
    //АДРЕС С СУБКАТЕГОРИЯМИ (Образец)
    //private const BASE_URL = 'https://apps.apple.com/genre/id7001';
    private const RE_VALIDATION_IDENTIFIER = '';
    private const MAX_RETRY = 5;

    private EntityManagerInterface $em;
    private HttpBrowserProxy $proxyBrowser;
    private AppStoreCategoryRepository $categoryRepository;
    private AppStoreCategoryRatingParser $categoryRatingParser;


    public function __construct(
        EntityManagerInterface $em,
        HttpBrowserProxy $proxyBrowser,
        AppStoreCategoryRepository $categoryRepository,
        AppStoreCategoryRatingParser $categoryRatingParser
    )
    {
        $this->em = $em;
        $this->proxyBrowser = $proxyBrowser;
        $this->categoryRepository = $categoryRepository;
        $this->categoryRatingParser = $categoryRatingParser;
    }

    public function execute(string $countryCode): void
    {
        $this->getCategoryInfo($countryCode);
    }

    private function getCategoryInfo(string $countryCode = 'US', int $retry = 0, string $proxyServer = null): void
    {
        dump("Category parsing...");
        $countryCode = strtolower($countryCode);
        $url = sprintf(self::BASE_URL, $countryCode);
        $browser = $this->proxyBrowser;
        $browserRequest = $browser->urlRequest('GET', $url, [], self::RE_VALIDATION_IDENTIFIER, [], $proxyServer);

        if ($browserRequest == null){
            if ($retry < self::MAX_RETRY){
                $proxyServer = $browser->getProxy($url);
                $this->getCategoryInfo(++$retry, $proxyServer);
            }
        }else{
            $this->checkCategory($countryCode, $browserRequest);
        }
    }

    private function checkCategory(string $countryCode, HttpBrowser $browserRequest): void
    {
        $categoryLinks = $browserRequest->getCrawler()
            ->filter('div[id="genre-nav"]')
            ->filter('a')->each(function ($node, $i) {
                return [$node->text() => $node->attr('href')];
            });

        foreach ($categoryLinks as $categoryData){
            $categoryName = key($categoryData);
            $categoryUrl = $categoryData[$categoryName];
            $categoryId = AppStorePublisherParser::getIdByUrl($categoryUrl);

            $currCategory = $this->categoryRepository->findOneBy(['externalId' => $categoryId]);

            if (!$currCategory) {
                $currCategory = (new AppStoreCategory())
                    ->setExternalId($categoryId)
                    ->setName($categoryName);

                $this->em->persist($currCategory);
                $this->em->flush();
            }

            $this->categoryRatingParser->execute($countryCode, $currCategory);
            //ЗАПУСКАЕМ ПРОВЕРКУ ПО ДИРЕКТОРИИ
        }
    }
}
