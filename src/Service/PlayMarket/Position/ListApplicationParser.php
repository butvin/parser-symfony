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

class ListApplicationParser
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
     * Returns an array of applications from the Google PlayMarket for the specified category.
     */
    final public function execute(
        Application $application,
        string $country,
        string $locale = Application::DEFAULT_LOCALE,
        ?AgeEnum $ageCriteria = null,
        int $limit = GPlayApps::UNLIMIT
    ): ?array {
        $rating = mb_strtoupper(Position::RATING_TYPE_CATEGORY_LIST);
        $category = mb_strtoupper(($application->getCategory())->getExternalId());
        dump(sprintf("Parse index in %s for rating -> %s", $category, $rating));

        $proxy = $this->proxies->current();
        dump(sprintf("[%s] Trying request with proxy %s", get_class($this), $proxy));

        try {
            $listApps = $this->playMarket
                ->setTimeout(5)
                ->setDefaultLocale($locale)
                ->setDefaultCountry($country)
                ->setProxy($proxy)
                ->getListApps($category, $ageCriteria, $limit);
            /** @var App[] */
        } catch (\Throwable $e) {
            dump(sprintf("IN: [%s] EXCEPTION: [%s] | MSG: '%s'", get_class($this), get_class($e), $e->getMessage()));
            $this->proxies->next();
            return $this->execute($application, $country);
        }

        dump(sprintf("[%s] Success request with proxy %s", get_class($this), $proxy));

        if (empty($listApps)) {
            dump(sprintf("[EMPTY SET] >>> #%s|%s country: %s | category: %s | rating: %s",
                $application->getId(),$application->getName(), $country, $category, $rating
            ));

            return null;
        }

        return array_keys($listApps);
    }
}
