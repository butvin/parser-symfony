<?php

declare(strict_types=1);

namespace App\Service\PlayMarket\Position;

use App\Entity\Application;
use App\Entity\Position;
use App\Service\ProxyChecker;
use Nelexa\GPlay\Enum\AgeEnum;
use Nelexa\GPlay\GPlayApps;
use Nelexa\GPlay\Model\App;

class NewApplicationParser
{
    private GPlayApps $playMarket;
    private \Iterator $proxies;

    public function __construct(
        ProxyChecker $proxyChecker,
        GPlayApps $playMarket
    ) {
        $this->proxies = $proxyChecker->getSecureProxies();
        $this->playMarket = $playMarket;
    }

    /**
     * Returns an array of NEW applications from the PlayMarket for the specified category.
     */
    final public function execute(
        Application $application,
        string $country,
        int $retry = 0,
        string $locale = Application::DEFAULT_LOCALE,
        ?AgeEnum $ageCriteria = null,
        int $limit = GPlayApps::UNLIMIT
    ): ?array {
        $rating = Position::RATING_TYPE_CATEGORY_NEW;
        $category = mb_strtoupper(($application->getCategory())->getExternalId());

        dump(sprintf("Parse index in %s for rating -> %s ***", $category, $rating));

        $proxy = $this->proxies->current();
        dump(sprintf("[%s] Trying request with proxy %s", get_class($this), $proxy));
        try {
            /** @var App[] */
            $newApps = $this->playMarket
                ->setTimeout(5)
                ->setDefaultLocale($locale)
                ->setDefaultCountry($country)
                ->setProxy($proxy)
                ->getNewApps($category, $ageCriteria, $limit);
        } catch (\Throwable $e) {
            if ($retry <= PositionAggregator::MAX_RETRY) {
                dump($e);
                $this->proxies->next();
                return $this->execute($application, $country, $retry + 1);
            }
        }

        dump(sprintf("[%s] Success request with proxy %s", get_class($this), $proxy));

        if (empty($newApps)) {
            dump(sprintf("[EMPTY SET] >>> #%s|%s country: %s | category: %s | rating: %s",
                $application->getId(),$application->getName(), $country, $category, $rating
            ));

            return null;
        }

        return array_keys($newApps);
    }
}