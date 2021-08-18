<?php

namespace App\MessageHandler;

use App\Helper\DataBaseReconnect;
use App\Message\AppStorePublisherErrorMessage;
use App\Message\AppStorePublisherParseMessage;
use App\Repository\AppStorePublisherRepository;
use App\Service\AppStorePublisherParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class AppStorePublisherParseHandler implements MessageHandlerInterface
{
    use DataBaseReconnect;

    protected MessageBusInterface $bus;

    private EntityManagerInterface $em;
    private AppStorePublisherRepository $publisherRepository;
    private AppStorePublisherParser $publisherParser;

    public function __construct(
        EntityManagerInterface $em,
        AppStorePublisherRepository $publisherRepository,
        MessageBusInterface $bus,
        AppStorePublisherParser $publisherParser
    ) {
        $this->em = $em;
        $this->publisherRepository = $publisherRepository;
        $this->bus = $bus;
        $this->publisherParser = $publisherParser;
    }

    public function __invoke(AppStorePublisherParseMessage $message)
    {
        $publisher = $this->publisherRepository->find($message->getId());

        if (null === $publisher) {
            return;
        }

        try {
            $this->publisherParser->execute($publisher);
        } catch (\Exception $exception) {
            $publisher->setReason($exception->getMessage());

            $this->bus->dispatch(new AppStorePublisherErrorMessage(
                $publisher->getId(),
                $publisher->getReason(),
                $exception->getTraceAsString()
            ));
        }

        $this->reconnect($this->em);
        $this->em->flush();
    }
}
