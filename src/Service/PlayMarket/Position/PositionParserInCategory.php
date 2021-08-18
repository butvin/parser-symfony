<?php

declare(strict_types=1);

namespace App\Service\PlayMarket\Position;

use App\Entity\Application;
use App\Entity\Category;
use App\Entity\Position;
use App\Service\ProxyChecker;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use Nelexa\GPlay\GPlayApps;
use App\Repository\ApplicationRepository;
use Symfony\Component\HttpClient\Exception\TransportException;

class PositionParserInCategory
{
    private \Iterator $proxies;
    private GPlayApps $playMarket;
    private PositionCreator $positionCreator;
    private ApplicationRepository $applicationRepository;

    private const MAX_RETRY = 4;

    public function __construct(
        ProxyChecker $proxyChecker,
        GPlayApps $playMarket,
        PositionCreator $positionCreator,
        ApplicationRepository $applicationRepository
    ) {
        $this->proxies = $proxyChecker->getSecureProxies();
        $this->playMarket = $playMarket
            ->setDefaultLocale(Application::DEFAULT_LOCALE)
            ->setTimeout(5)
            ->setConcurrency(1);
        $this->positionCreator = $positionCreator;
        $this->applicationRepository = $applicationRepository;
    }

    final public function execute(string $country, Category $category, int $retry = 0): void
    {
        $proxy = $this->proxies->current();
        $gplay = $this->playMarket->setDefaultCountry($country);
        $data = [];

        try {
            $data[Position::RATING_TYPE_CATEGORY_LIST] = $gplay
                ->getListApps($category->getExternalId(), null, GPlayApps::UNLIMIT);

            $data[Position::RATING_TYPE_CATEGORY_TOP] = $gplay
                ->getTopApps($category->getExternalId(), null, GPlayApps::UNLIMIT);

            $data[Position::RATING_TYPE_CATEGORY_NEW] = $gplay
                ->getNewApps($category->getExternalId(), null, GPlayApps::UNLIMIT);

        } catch (TransportException | RequestException | ConnectException $e) {
            if ($retry <= self::MAX_RETRY) {
                dump($e);
                dump(sprintf("%s Retrying (%s)", get_class($e), $retry));
                $this->playMarket->setProxy($proxy);
                $this->proxies->next();
                dump(sprintf("[%s] Trying request with proxy %s", get_class($this), $proxy));
                $this->execute($country, $category, $retry +1);
            } else {
                return;
            }
        }

        if (
            empty($data[Position::RATING_TYPE_CATEGORY_LIST]) &&
            empty($data[Position::RATING_TYPE_CATEGORY_TOP]) &&
            empty($data[Position::RATING_TYPE_CATEGORY_NEW])
        ) {
            dump("EMPTY DATA");
            dump(sprintf("[%s] Trying request with proxy %s", get_class($this), $proxy));
            $this->execute($country, $category, $retry);
        }

        dump(sprintf("\n[SUCCESS REQUESTS] %s >>> \n proxy %s\n\n", get_class($this), $proxy));
        $this->bulkStore($country, $data, $category);

    }

    private function bulkStore(string $country, array $list, Category $category): void
    {
        $ratingTypes = array_replace(Position::RATING_TYPES, $list);

        $appRatingList = array_filter($ratingTypes,
            static fn($v, $k): bool => !is_string($v) && is_array($v),
            ARRAY_FILTER_USE_BOTH
        );

        $applications = $this->applicationRepository->getPlayMarketAppsByCategory($category);

        $creatorService = $this->positionCreator;
        foreach ($appRatingList as $ratingType => $apps) {
            if (is_array($apps) && !empty($apps) && isset(Position::RATING_TYPES[$ratingType])) {
                array_walk($applications,
                    static fn(Application $application) => $creatorService->create(
                        $application,
                        $country,
                        array_keys($apps),
                        $ratingType
                    )
                );
            }
        }
    }
}