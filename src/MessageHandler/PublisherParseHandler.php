<?php

namespace App\MessageHandler;

use App\Entity\Publisher;
use App\Helper\DataBaseReconnect;
use App\Message\PublisherErrorMessage;
use App\Message\PublisherParseMessage;
use App\Repository\PublisherRepository;
use App\Service\AbstractParser;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

class PublisherParseHandler implements MessageHandlerInterface
{
    use DataBaseReconnect;

    private EntityManagerInterface $em;

    private PublisherRepository $repository;

    protected MessageBusInterface $bus;

    /**
     * @var AbstractParser[]
     */
    private iterable $parsers;

    public function __construct(
        EntityManagerInterface $em,
        PublisherRepository $repository,
        MessageBusInterface $bus,
        iterable $parsers
    ) {
        $this->em = $em;
        $this->repository = $repository;
        $this->bus = $bus;
        $this->parsers = $parsers;
    }

    public function __invoke(PublisherParseMessage $message)
    {
        $publisher = $this->repository->find($message->getId());

        if (null === $publisher) {
            return;
        }

        try {
            $this->getParser($publisher)->execute($publisher);
        } catch (\Exception $exception) {
            $publisher->setReason($exception->getMessage());

            $this->bus->dispatch(new PublisherErrorMessage(
                $publisher->getId(),
                $publisher->getReason(),
                $exception->getTraceAsString()
            ));
        }

        $this->reconnect($this->em);
        $this->em->flush();
    }

    private function getParser(Publisher $publisher): AbstractParser
    {
        foreach ($this->parsers as $parser) {
            if ($parser->getExternalId($publisher->getUrl())) {
                return $parser;
            }
        }

        throw new \LogicException(sprintf(
            'Undefined parser from url "%s"',
            $publisher->getUrl()
        ));
    }
}
