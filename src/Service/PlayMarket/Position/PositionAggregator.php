<?php

declare(strict_types=1);

namespace App\Service\PlayMarket\Position;

use App\Entity\Application;
use App\Entity\Position;
use App\Entity\Publisher;

/**
 * @package  Nelexa\GPlay\GPlayApps
 * @link  https://github.com/Ne-Lexa/google-play-scraper/blob/master/docs/README.md#gplayappsgetappinfo-docs
 */
class PositionAggregator
{
    public const PARAM_ID = 'id';
    public const PARAM_LOCALE = 'hl';
    public const PARAM_COUNTRY = 'gl';
    private string $locale = Application::DEFAULT_LOCALE;

    private TopApplicationParser $topApplicationParser;
    private ListApplicationParser $listApplicationParser;
    private NewApplicationParser $newApplicationParser;
    private PositionCommonParser $positionCommonParser;
    private PositionCreator $positionCreator;

    public function __construct(
        PositionCreator $positionCreator,
        TopApplicationParser $topApplicationParser,
        ListApplicationParser $listApplicationParser,
        NewApplicationParser $newApplicationParser,
        PositionCommonParser $positionCommonParser
    ) {
        $this->positionCreator = $positionCreator;
        $this->listApplicationParser = $listApplicationParser;
        $this->topApplicationParser = $topApplicationParser;
        $this->newApplicationParser = $newApplicationParser;
        $this->positionCommonParser = $positionCommonParser;
    }

    /**
     * @throws \Exception
     */
    final public function execute(Application $application, string $countryCode): void
    {
        /* App is not product of PlayMarket */
        if ($application->getPublisher()->getType() !== Publisher::TYPE_PLAY_STORE) {
            dump(sprintf("App %s is not entity of %s",$application->getName(), Publisher::TYPE_PLAY_STORE));

            return;
        }

        /** GENERAL IN CATEGORY */
        $generalList = $this->listApplicationParser->execute($application, $countryCode);
        if (null !== $generalList) {
            $this->positionCreator->create($application, $countryCode, $generalList,Position::RATING_TYPE_CATEGORY_LIST);
        }

        /** TOP IN CATEGORY */
        $topList = $this->topApplicationParser->execute($application, $countryCode);
        if (null !== $topList) {
            $this->positionCreator->create($application, $countryCode, $topList, Position::RATING_TYPE_CATEGORY_TOP);
        }

        /** NEW IN CATEGORY */
        $newList = $this->newApplicationParser->execute($application, $countryCode);
        if (null !== $newList) {
            $this->positionCreator->create($application, $countryCode, $newList, Position::RATING_TYPE_CATEGORY_NEW);
        }

        /** COMMON = PAID & FREE & GENERAL on FRONT */
        $this->positionCommonParser->execute($application, $countryCode);

        dump(sprintf("Finished >>> [#%s] | %s via country: %s\nURL: %s",
            $application->getId(),
            $application->getName(),
            $countryCode,
            static::dropQueryParam($application->getUrl(), self::PARAM_COUNTRY)
        ));
    }

    public static function getExternalId(string $url): ?string
    {
        $query = parse_url($url, PHP_URL_QUERY);
        parse_str($query, $param);

        return $param[self::PARAM_ID] ?? null;
    }

    public static function dropQueryParam(string $url, string $param): string
    {
        $url = preg_replace("/(&|\?)" . preg_quote($param, null) . "=[^&]*$/", '', $url);

        return preg_replace("/(&|\?)" . preg_quote($param, null) . "=[^&]*&/", '$1', $url);
    }
}
