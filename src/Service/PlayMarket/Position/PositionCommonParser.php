<?php

declare(strict_types=1);

namespace App\Service\PlayMarket\Position;

use App\Entity\Application;
use App\Entity\Position;
use App\Service\ProxyChecker;
use Nelexa\GPlay\Enum\AgeEnum;
use Nelexa\GPlay\GPlayApps;
use Nelexa\GPlay\Model\App;
use Symfony\Component\HttpClient\Exception\TransportException;
use Nelexa\GPlay\Exception\GooglePlayException;

class PositionCommonParser
{
    private GPlayApps $playMarket;
    private PositionCreator $positionCreator;
    private \Iterator $proxies;

    public function __construct(
        ProxyChecker $proxyChecker,
        GPlayApps $playMarket,
        PositionCreator $positionCreator
    ) {
        $this->proxies = $proxyChecker->getSecureProxies();
        $this->playMarket = $playMarket;
        $this->positionCreator = $positionCreator;
    }

    /**
     * Returns an array of front applications from the PlayMarket.
     * @throws \Exception
     */
    final public function execute(
        Application $application,
        string $country,
        string $locale = Application::DEFAULT_LOCALE,
        ?AgeEnum $ageCriteria = null,
        ?int $limit = GPlayApps::UNLIMIT
    ): void {
        $proxy = $this->proxies->current();
        dump(sprintf("[%s] Trying request with proxy %s", get_class($this), $proxy));

        try {
            /** @var App[] */
            $apps = $this->playMarket
                ->setTimeout(5)
                ->setDefaultLocale($locale)
                ->setDefaultCountry($country)
                ->setProxy($proxy)
                ->getListApps(null, $ageCriteria, $limit);

            if (empty($apps)) {
                dump(sprintf(
                         "[EMPTY SET COMMON] >>> #%s|%s country: %s ",
                         $application->getId(),$application->getName(), $country
                     ));

                $this->proxies->next();
                $this->execute($application, $country);
            }
        } catch (\Throwable $e) {
            dump(sprintf("IN: [%s] EXCEPTION: [%s] | MSG: '%s'", get_class($this), get_class($e), $e->getMessage()));
            $this->proxies->next();
            $this->execute($application, $country);
        }

        dump(sprintf("[%s] Success request with proxy %s", get_class($this), $proxy));

        /* front general */
        $this->positionCreator->create($application, $country, array_keys($apps), Position::RATING_TYPE_MAIN_LIST);

        /* paid */
        if ($appsPaid = array_filter($apps, static fn (App $app, string $appKey) => ! $app->isFree(), ARRAY_FILTER_USE_BOTH)) {
            $this->positionCreator->create($application, $country, array_keys($appsPaid), Position::RATING_TYPE_MAIN_PAID);
        }

        /* free */
        if ($appsFree = array_filter($apps, static fn (App $app, string $appKey) => $app->isFree(), ARRAY_FILTER_USE_BOTH)) {
            $this->positionCreator->create($application, $country, array_keys($appsFree), Position::RATING_TYPE_MAIN_FREE);
        }
    }
}