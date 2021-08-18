<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Publisher;
use App\Helper\DataBaseReconnect;
use App\Repository\ApplicationRepository;
use App\Repository\CategoryRepository;
use App\Service\PlayMarket\Position\PositionParserFront;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use App\Service\PlayMarket\Position\PositionAggregator;
use App\Service\PlayMarket\Position\PositionParserInCategory;
use App\Message\PositionParseMessage;

class PositionParseHandler implements MessageHandlerInterface
{
    use DataBaseReconnect;

    private EntityManagerInterface $em;
    private PositionAggregator $positionAggregator;
    private PositionParserInCategory $positionParserInCategory;
    private PositionParserFront $positionParserFront;
    private ApplicationRepository $applicationRepository;
    private CategoryRepository $categoryRepository;

    public function __construct(
        PositionAggregator $positionAggregator,
        PositionParserInCategory $positionParserInCategory,
        PositionParserFront $positionParserFront,
        EntityManagerInterface $em,
        ApplicationRepository $applicationRepository,
        CategoryRepository $categoryRepository
    ) {
        $this->em = $em;
        $this->positionParserInCategory = $positionParserInCategory;
        $this->positionParserFront = $positionParserFront;
        $this->positionAggregator = $positionAggregator;
        $this->applicationRepository = $applicationRepository;
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * @throws \Exception
     */
    final public function __invoke(PositionParseMessage $message): void
    {
        $applications = $this->applicationRepository->getPlayMarketApplications();
        if (null === $applications) {
            return;
        }

        $country = $message->getCountry();
        if (null === $country) {
            return;
        }

        $uniqCategories = $this->applicationRepository->getDistinctCategories();
        $categories = array_column($uniqCategories, 'external_id', 'id');

        foreach ($categories as $id => $externalId) {
            $category = $this->categoryRepository->find($id);
            try {
                $this->positionParserInCategory->execute($country, $category);
            } catch (\Throwable | \LogicException $e) {
                dump($e);
            }
        }

        $this->positionParserFront->execute($country);

        $this->reconnect($this->em);
        $this->em->flush();
    }
}