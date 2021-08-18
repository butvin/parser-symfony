<?php

declare(strict_types=1);

namespace App\Command;

use App\Helper\IntlHelper;
use App\Message\PositionParseMessage;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Messenger\MessageBusInterface;

class PositionParseCommand extends Command
{
    protected static $defaultName = 'app:position:parse';
    private MessageBusInterface $bus;

    public function __construct(MessageBusInterface $bus)
    {
        $this->bus = $bus;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('url', InputArgument::OPTIONAL, 'PlayMarket apps positions parser');
    }

    final public function execute(InputInterface $input, OutputInterface $output): ?int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(mb_strtoupper("Position's parser started..."));

        $countries = IntlHelper::TEST_COUNTRIES;
        if (count($countries) < 1) {
            $io->error("No countries to parse");
            return 0;
        }

        $io->writeln(
            '<info>'.
                sprintf("Countries: <question>%s</question>", count($countries))
            .'</info>'
        );

        foreach ($countries as $countryCode => $countryName) {
            $io->writeln(
                '<question>'.
                    sprintf("Dispatched >> '%s' country | %s", $countryCode, $countryCode)
                .'</question>'
            );

            $this->bus->dispatch(new PositionParseMessage($countryCode));
        }

        $io->success(
            sprintf("Done! Pushed %s countries to queue", count($countries))
        );

        return 0;
    }
}
