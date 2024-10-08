<?php

namespace App\Command;

use App\Message\PublisherParseMessage;
use App\Repository\PublisherRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

class PublisherParseCommand extends Command
{
    protected static $defaultName = 'app:parse:publishers';

    private MessageBusInterface $bus;
    private PublisherRepository $publisherRepository;

    public function __construct(MessageBusInterface $bus, PublisherRepository $publisherRepository)
    {
        $this->bus = $bus;
        $this->publisherRepository = $publisherRepository;
        parent::__construct();
    }

    final public function configure(): void
    {
        $this->addArgument('url', InputArgument::OPTIONAL, 'Google apps publishers parser');
    }

    final public function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Run publisher parsers');

        $publishers = $this->publisherRepository->findAll();
        if (empty($publishers)) {
            $io->warning("No countries to parse");

            return 0;
        }

        foreach ($publishers as $publisher) {
            $io->writeln(
                sprintf("Push publisher to queue >>> #%s | %s", $publisher->getId(), $publisher->getUrl())
            );

            $this->bus->dispatch(
                new PublisherParseMessage($publisher->getId())
            );
        }

        $io->success(sprintf("Done! Pushed %s publishers to queue", count($publishers)));

        return 0;
    }
}
