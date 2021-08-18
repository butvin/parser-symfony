<?php

namespace App\Command;

use App\Entity\AppStoreCategory;
use App\Entity\AppStorePublisher;
use App\Message\AppStorePublisherParseMessage;
use App\Repository\AppStorePublisherRepository;
use App\Service\AppStoreCategoryParser;
use App\Service\AppStorePublisherParser;
use App\Service\AppStoreTotalRatingParser;
use App\Service\AppStoreCategoryRatingParser;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;


class TestCommandR extends Command
{
    protected static $defaultName = 'app:test';

    protected AppStoreCategoryParser $categoryParser;

    protected AppStorePublisherParser $parser;

    protected AppStorePublisherRepository $repository;

    public function __construct(AppStoreCategoryParser $categoryParser, AppStorePublisherParser $parser, AppStorePublisherRepository $repository)
    {
        $this->categoryParser = $categoryParser;
        $this->parser = $parser;
        $this->repository = $repository;
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        //$this->categoryParser->execute();

        //$io = new SymfonyStyle($input, $output);
        //$io->title('Run publisher parsers');

        foreach ($this->repository->findAll() as $publisher) {
            //$io->text(sprintf('Send publisher #%s to queue.', $publisher->getId()));
            //$this->parser->execute($publisher);
            //$message =
                new AppStorePublisherParseMessage($publisher->getId());
            $this->bus->dispatch($message);
        }
        //
        //if (empty($a)){
        //    dump("ERROR");
       // }

        return 0;
    }
}


//ДОДЕЛАТЬ!!!
//1. Валидация ответа - маркеры
//2. Предупреждение валидация одинаковый ИД
//3. Удаление записей при удалении паблишера?????
//4. Проверка на парсинг категорий, должен быть хотя бы 1 юзер
//+5. Загрузка иконок через прокси сервер
