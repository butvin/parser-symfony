<?php

declare(strict_types=1);

namespace App\Service;

use App\Helper\IntlHelper;
use App\Entity\AppStoreCategory;
use App\Entity\AppStorePosition;
use App\Repository\AppStoreApplicationRepository;
use App\Repository\AppStorePositionRepository;
use Doctrine\ORM\EntityManagerInterface;

class AppStorePositionCreator
{
    private EntityManagerInterface $em;
    private AppStorePositionRepository $positionRepository;
    private AppStoreApplicationRepository $applicationRepository;

    public function __construct(
        EntityManagerInterface $em,
        AppStorePositionRepository $positionRepository,
        AppStoreApplicationRepository $applicationRepository
    )
    {
        $this->em = $em;
        $this->positionRepository = $positionRepository;
        $this->applicationRepository = $applicationRepository;
    }

    final public function create(
        string $type,
        array $appIds,
        string $country,
        AppStoreCategory $category = null
    ): void
    {
        $qty = count($appIds);
        $appIds = array_flip($appIds);

        $appsList = $this->applicationRepository->findAll();
        foreach ($appsList as $application) {

            if (isset($appIds[$application->getExternalId()])) {

                $currIndex = $appIds[$application->getExternalId()];

                $diffIndex = null;
                if (null !== $currIndex) {
                    $currIndex = $currIndex+1;
                    $prevIndex = $this->positionRepository->findLastResult($country, $type, $application->getId());
                    if ($prevIndex) {
                        $diffIndex = $prevIndex[0]['currIndex'] - $currIndex;
                    }
                }

                $position = (new AppStorePosition($application))
                    ->setCategory($category)
                    ->setCountry($country)
                    ->setPrevIndex($diffIndex)
                    ->setCurrIndex($currIndex)
                    ->setRatingType($type)
                    ->setTotalQuantity($qty);

                $this->em->persist($position);
                $this->em->flush();

                $application->addStorePosition($position);

                dump(
                    sprintf(
                        "\n       [SAVED] position -> #%s | %s | [%s of %s] country -> %s|%s\n",

                        $position->getId(),
                        mb_strtoupper($position->getRatingType()),
                        $position->getCurrIndex(),
                        $position->getTotalQuantity(),
                        $position->getCountry(),
                        IntlHelper::COUNTRIES[strtoupper($position->getCountry())]
                    )
                );
            }else{
                if ($application->getCategory() === $category OR $category == null){
                    $position = (new AppStorePosition($application))
                        ->setCategory($category)
                        ->setCountry($country)
                        ->setRatingType($type)
                        ->setTotalQuantity($qty);

                    $this->em->persist($position);
                    $this->em->flush();
                }
            }
        }
    }
}