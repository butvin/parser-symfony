<?php

declare(strict_types=1);

namespace App\Service\PlayMarket\Position;

use App\Entity\Application;
use App\Entity\Position;
use App\Service\ProxyChecker;
use GuzzleHttp\Exception\RequestException;
use Nelexa\GPlay\GPlayApps;
use Nelexa\GPlay\Model\App;
use GuzzleHttp\Exception\ConnectException;
use App\Repository\ApplicationRepository;
use Symfony\Component\HttpClient\Exception\TransportException;

class PositionParserFront
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
            ->setConcurrency(1)
            ->setTimeout(5)
        ;
        $this->positionCreator = $positionCreator;
        $this->applicationRepository = $applicationRepository;
    }

    /**
     * Gets (all, top, new) apps from pages:
     * @link https://play.google.com/store/apps
     * @link https://play.google.com/store/apps/top
     * @link https://play.google.com/store/apps/new
     * @throws \Exception
     */
    final public function execute(string $country, int $retry = 0): void
    {
        $proxy = $this->proxies->current();
        $gplay = $this->playMarket->setDefaultCountry($country);
        $data = [];

        try {
            $data[Position::RATING_TYPE_MAIN_LIST] = $gplay->getListApps();
            $data[Position::RATING_TYPE_MAIN_TOP] = $gplay->getTopApps();
            $data[Position::RATING_TYPE_MAIN_NEW] = $gplay->getNewApps();
        } catch (TransportException | RequestException | ConnectException $e) {
            if ($retry <= self::MAX_RETRY) {
                dump($e);
                dump(sprintf("\nIn class: %s\nRetrying (%s)\nProxy: %s\n", get_class($this), $retry, $proxy));
                $gplay->setProxy($proxy);
                $this->proxies->next();
                $this->execute($country, $retry + 1);
            } else {
                return;
            }
        }

        if (empty($data)) {
            dump(sprintf("EMPTY DATA %s Retrying (%s) previous request", get_class($this), $retry));
            dump(sprintf("[%s] Trying request with proxy %s", get_class($this), $proxy ?? 'no proxy'));
            $this->execute($country, $retry);
        }

        dump(sprintf("\n[SUCCESS REQUESTS] %s >>> \n proxy %s\n\n", get_class($this), $proxy));
        $this->bulkStore($country, $data);
    }

    /**
     * @throws \Exception
     */
    private function bulkStore(string $country, array $list): void
    {
        $ratingTypes = array_replace(Position::RATING_TYPES, $list);

        $appRatingList = array_filter($ratingTypes,
            static fn($v, $k): bool => !is_string($v) && is_array($v),
            ARRAY_FILTER_USE_BOTH
        );

        if ($appRatingList[Position::RATING_TYPE_MAIN_LIST]) {
            // Paid list preparing
            $appRatingList[Position::RATING_TYPE_MAIN_PAID] = array_filter(
                $list[Position::RATING_TYPE_MAIN_LIST],
                static fn (App $app, string $appKey) => ! $app->isFree(),
                ARRAY_FILTER_USE_BOTH
            );

            // Free list preparing
            $appRatingList[Position::RATING_TYPE_MAIN_FREE] = array_filter(
                $list[Position::RATING_TYPE_MAIN_LIST],
                static fn (App $app, string $appKey) => $app->isFree(),
                ARRAY_FILTER_USE_BOTH
            );
        }

        $applications = $this->applicationRepository->getPlayMarketApplications();
        if(null === $applications) {
            dump("no apps found");
            return;
        }

        $creatorService = $this->positionCreator;

        foreach ($appRatingList as $ratingType => $apps) {
            if (is_array($apps) && !empty($apps) && isset(Position::RATING_TYPES[$ratingType])) {
                array_walk(
                    $applications,
                     static fn(Application $application) => $creatorService->create(
                         $application, $country, array_keys($apps), $ratingType
                    )
                );
            }
        }
    }
}