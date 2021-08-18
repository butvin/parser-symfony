<?php

namespace App\Service;

use App\Entity\Publisher;
use App\Helper\DataBaseReconnect;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\Messenger\MessageBusInterface;

abstract class AbstractParser
{
    use DataBaseReconnect;

    protected const WORKDIR = '/application/public/icons';

    protected const MAX_RETRY = 2;

    protected EntityManagerInterface $em;

    protected MessageBusInterface $bus;

    protected \Iterator $proxies;

    private const REQUEST_TIMEOUT = 8;

    public function __construct(EntityManagerInterface $em, MessageBusInterface $bus, ProxyChecker $checker)
    {
        $this->em = $em;
        $this->bus = $bus;
        $this->proxies = $checker->getSecureProxies();

        (new Filesystem())->mkdir(self::WORKDIR);
    }

    abstract public function getType(): string;

    abstract public function getExternalId(string $url): ?string;

    abstract public function execute(Publisher  $publisher, int $retry = 0): void;

    abstract protected function getReValidation(): string;

    protected function request(string $url, int $retry = 0): HttpBrowser
    {
        $proxy = $this->proxies->current();

        $browser = new HttpBrowser(new CurlHttpClient([
            'proxy'        => $proxy,
            'max_duration' => self::REQUEST_TIMEOUT,
        ]));

        try {
            dump(sprintf('Request "%s" with proxy "%s"', $url, $proxy));
            $browser->request('GET', $url);
        } catch (TransportException $exception) {
            dump($exception->getMessage());
            dump('Remove proxy');
            $this->proxies->next();

            return $this->request($url, $retry);
        }

        $responseCode = $browser->getResponse()->getStatusCode();

        if ($responseCode !== 200) {
            dump(sprintf('Response code "%s"', $responseCode));
            if ($retry <= self::MAX_RETRY) {
                dump('Retry');
                $this->proxies->next();

                return $this->request($url, $retry + 1);
            }

            throw new \LogicException(sprintf('Response code "%s"', $responseCode));
        }

        if (!preg_match($this->getReValidation(), $browser->getResponse()->getContent())) {
            dump('Not found validation element!');
            if ($retry <= self::MAX_RETRY) {
                dump('Retry');
                $this->proxies->next();

                return $this->request($url, $retry + 1);
            }

            throw new \LogicException('Not found validation element!');
        }

        return $browser;
    }

    protected function download(string $url, string $path): void
    {
        $proxy = $this->proxies->current();

        $browser = new HttpBrowser(new CurlHttpClient([
            'proxy'        => $proxy,
            'max_duration' => self::REQUEST_TIMEOUT,
        ]));

        try {
            dump(sprintf('Download "%s" with proxy "%s"', $url, $proxy));
            $browser->download($url, $path);
        } catch (TransportException $exception) {
            dump('Remove proxy');
            $this->proxies->next();

            $this->download($url, $path);
        }
    }
}
