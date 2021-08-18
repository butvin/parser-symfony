<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Helper\IntlHelper;
use App\Helper\DataBaseReconnect;
use App\Message\AppStorePositionParseMessage;
use App\Service\AppStoreCategoryParser;
use App\Service\AppStoreTotalRatingParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class AppStorePositionParseHandler implements MessageHandlerInterface
{
    use DataBaseReconnect;

    private EntityManagerInterface $em;
    private AppStoreCategoryParser $categoryParser;
    private AppStoreTotalRatingParser $totalRatingParser;

    public function __construct(
        EntityManagerInterface $em,
        AppStoreCategoryParser $categoryParser,
        AppStoreTotalRatingParser $totalRatingParser
    ) {
        $this->em = $em;
        $this->categoryParser = $categoryParser;
        $this->totalRatingParser = $totalRatingParser;
    }

    final public function __invoke(AppStorePositionParseMessage $message): void
    {
        $countryCode = $message->getCountry();

        if (null === $countryCode) {
            return;
        }

        $this->categoryParser->execute($countryCode);
        $this->totalRatingParser->execute($countryCode);
        dump(
            sprintf(
                "       [RECEIVE MESSAGE] >>> Rating: country -> %s|%s ",
                $countryCode,
                IntlHelper::COUNTRIES[$countryCode]
            )
        );

        $this->reconnect($this->em);
        $this->em->flush();
    }
}