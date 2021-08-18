<?php

declare(strict_types=1);

namespace App\Service\PlayMarket\Position;

use App\Entity\Application;
use App\Entity\Position;
use App\Repository\PositionRepository;
use App\Helper\IntlHelper;
use Doctrine\ORM\EntityManagerInterface;

class PositionCreator
{
    private EntityManagerInterface $em;
    private PositionRepository $positionRepository;

    public function __construct(
        EntityManagerInterface $em,
        PositionRepository $positionRepository
    ) {
        $this->em = $em;
        $this->positionRepository = $positionRepository;
    }

    /**
     * @throws \Exception
     */
    final public function create(Application $application, string $country, array $list, string $ratingType): Position
     {
        $index = $this->getIndex($application, $list);

        $position = (new Position($application, $application->getCategory()))
            ->setLocale(Application::DEFAULT_LOCALE)
            ->setCountry($country)
            ->setRatingType($ratingType)
            ->setIndex($index)
            ->setTotalQuantity(count($list))
        ;

        $prevPosition = $this->positionRepository->getPrevPosition($application, $ratingType, $country);
        if (null !== $prevPosition) {
            $position->setPrevIndex($prevPosition->getIndex());
        }

        $this->em->persist($position);
        $this->em->flush();

        $application->addPosition($position);
        dump($this->printPositionInfo($position));

        return $position;
    }

    private function printPositionInfo(Position $position): string
    {
        $nowTime = (new \DateTime())->format('H:i:s');

        $pattern = "
            %s | [POSITION] >>>
            #%s |rating: %s
            current: %s of %s | prev: %s
            country: %s | %s
        ";

        dump($position);

        return sprintf($pattern,
            $nowTime,
            $position->getId(),
            mb_strtoupper(Position::RATING_TYPES[$position->getRatingType()]),
            $position->getIndex(),
            $position->getTotalQuantity(),
            $position->getPrevIndex(),
            $position->getCountry(),
            IntlHelper::COUNTRIES[$position->getCountry()]
        );
    }

    private function getIndex(Application $application, array $list): int
    {
        return in_array($application->getExternalId(), $list, true) ?
            (int)(array_search($application->getExternalId(), $list, true)) + 1 : -1;
    }
}