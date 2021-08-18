<?php

namespace App\Command;

use App\Message\PublisherParseMessage;
use App\Repository\PublisherRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

class PublisherParseCommand extends Command
{
    protected static $defaultName = 'app:publisher:parse';

    private MessageBusInterface $bus;

    private PublisherRepository $repository;

    public function __construct(MessageBusInterface $bus, PublisherRepository $repository)
    {
        $this->bus = $bus;
        $this->repository = $repository;

        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Run publisher parsers');

        foreach ($this->repository->findAll() as $publisher) {
            $io->text(sprintf('Send publisher #%s to queue.', $publisher->getId()));

            $message = new PublisherParseMessage($publisher->getId());
            $this->bus->dispatch($message);
        }

        return 0;
    }
}
