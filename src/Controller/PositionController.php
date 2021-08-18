<?php

declare(strict_types=1);

namespace App\Controller;

use App\EventListener\PositionResponseListener;
use App\Event\PositionResponseEvent;
use App\DTO\DatepickerFormDTO;
use App\Entity\Application;
use App\Entity\Position;
use App\Entity\Publisher;
use App\Form\DatepickerFormType;
use App\Repository\PositionRepository;
use App\Repository\ApplicationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\SerializerInterface;

class PositionController extends AbstractController
{
    protected PositionRepository $positionRepository;
    protected ApplicationRepository $applicationRepository;
    protected SerializerInterface $serializer;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(
        EventDispatcherInterface $eventDispatcher,
        SerializerInterface $serializer,
        PositionRepository $positionRepository,
        ApplicationRepository $applicationRepository
    ) {
        $this->eventDispatcher = $eventDispatcher;
        $this->serializer = $serializer;
        $this->positionRepository = $positionRepository;
        $this->applicationRepository = $applicationRepository;
    }

    /**
     * @throws \Exception
     */
    final public function show(Application $application, Request $request): Response
    {
        if ($application->getPublisher()->getType() !== Publisher::TYPE_PLAY_STORE) {
            return new Response(
                ['error' => sprintf("not app of %s apps", Publisher::TYPE_PLAY_STORE) ],
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }

        $form = $this->createForm(DatepickerFormType::class, new DatepickerFormDTO());

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $dateInput = $form->get('dateInput')->getData();
            $dateSelected = new \DateTime($dateInput);
        }

        if (!isset($dateSelected)) {
            $lastPosition = $this->positionRepository->findOneBy(['application' => $application], ['id' => 'DESC']);
            $dateSelected = $lastPosition ? $lastPosition->getCreatedAt() : new \DateTime();
        }

        $positions = $this->positionRepository->getDailyPositions($application, $dateSelected);

        $this->eventDispatcher->addListener(PositionResponseEvent::NAME, [
            new PositionResponseListener($this->serializer, $dateSelected),
            'onPositionResponse',
        ]);

        if ($request->isXmlHttpRequest()) {
            $this->eventDispatcher->dispatch(
                new PositionResponseEvent($request, $positions, $dateSelected),
                PositionResponseEvent::NAME
            );
        }

        return $this->render('positions/show.html.twig', [
            'application' => $application,
            'positions' => $positions,
            'form' => $form->createView(),
        ]);
    }
}
