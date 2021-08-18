<?php

namespace App\Service;

use Exception;
use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\HttpBrowser;
use Symfony\Component\HttpClient\CurlHttpClient;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Component\HttpClient\HttpClient;

class HttpBrowserProxy extends HttpBrowser
{
    protected \Iterator $proxies;

    private string $method;
    private string $url;
    private string $identifier;

    private array $params;
    private array $httpClientParam;

    private const MAX_RETRY = 3;
    private const REQUEST_TIMEOUT = 20;

    public function __construct(ProxyChecker $checker)
    {
        $this->proxies = $checker->getSecureProxies();
        parent::__construct();
    }

    public function getProxy(string $checkUrl,  string $method = 'GET'): ?string
    {
        $this->setStandardParams($method, $checkUrl);

        return $this->makeProxyCheck();
    }

    public function urlRequest(string $method, string $url, array $params = [], string $identifier = '', array $httpClientParam = [], ?string $proxy = null): ?HttpBrowser
    {
        $this->setStandardParams($method, $url, $params, $identifier, $httpClientParam);
        if (null == $proxy){
            return $this->makeStandardRequest();
        } else {
            return $this->makeProxyRequest($proxy);
        }
    }

    protected function getReValidation($identifier): string
    {
        return sprintf('/%s/i', $identifier);
    }

    private function setStandardParams(string $method, string $url, array $params = [], string $identifier = '', array $httpClientParam = [])
    {
        $this->method = $method;
        $this->url = $url;
        $this->params = $params;
        $this->identifier = $identifier;
        $this->httpClientParam = $httpClientParam;
    }

    private function makeProxyCheck(): string
    {
        dump("Getting proxy...");
        $proxy = $this->proxies->current();

        if (empty($proxy)) {
            throw new \LogicException(sprintf(PHP_EOL . 'No have valid proxy!'));
        }

        $browser = new HttpBrowser(
            HttpClient::create(
                array_merge(
                    $this->httpClientParam,
                    [
                        'proxy'        => $proxy,
                        'max_duration' => self::REQUEST_TIMEOUT
                    ]
                )
            )
        );

        try {
            dump(sprintf('Request "%s" with proxy "%s"', $this->url, $proxy));
            $browser->request($this->method, $this->url, $this->params);
        } catch (Exception $exception) {
            dump($exception->getMessage());
            dump("can`t get data by link SGG");
            $this->proxies->next();
            return $this->makeProxyCheck();
        }

        $responseCode = $browser->getResponse()->getStatusCode();
        if ($responseCode !== 200) {
            dump(sprintf('When parsing the page "%s", the server will return a response "%s"', $this->url, $responseCode));
            $this->proxies->next();
            return $this->makeProxyCheck();
        }

        if (!preg_match($this->getReValidation($this->identifier), $browser->getResponse()->getContent())) {
            dump('Not found validation element!');
            $this->proxies->next();
            return $this->makeProxyCheck();
        }

        return $proxy;
    }

    private function makeStandardRequest(int $retry = 0): ?HttpBrowser
    {
        if ($retry > self::MAX_RETRY) {
            dump('Not valid IP!');
            return null;
        }

        $browser = new HttpBrowser(HttpClient::create($this->httpClientParam));

        try {
            dump(sprintf('Request "%s"', $this->url));
            $browser->request($this->method, $this->url);
        } catch (Exception $exception) {
            dump($exception->getMessage());
            dump("can`t get data by link");
            return $this->makeStandardRequest(++$retry);
        }

        $responseCode = $browser->getResponse()->getStatusCode();
        if ($responseCode !== 200) {
            dump(sprintf('When parsing the page "%s", the server will return a response "%s"', $this->url, $responseCode));
            return $this->makeStandardRequest(++$retry);
        }

        if (!preg_match($this->getReValidation($this->identifier), $browser->getResponse()->getContent())) {
            dump('Not found validation element!');
            return $this->makeStandardRequest(++$retry);
        }

        return $browser;
    }

    private function makeProxyRequest(string $proxy, int $retry = 0): ?HttpBrowser
    {
        if ($retry > self::MAX_RETRY) {
            $this->proxies->next();
            return null;
        }

        $browser = new HttpBrowser(
            HttpClient::create(
                array_merge(
                    $this->httpClientParam,
                    [
                        'proxy'        => $proxy,
                        'max_duration' => self::REQUEST_TIMEOUT
                    ]
                )
            )
        );

        try {
            dump(sprintf('Request "%s" with proxy "%s"', $this->url, $proxy));
            $browser->request($this->method, $this->url, $this->params);
        } catch (Exception $exception) {
            dump($exception->getMessage());
            dump("can`t get data by link PMM");
            return $this->makeProxyRequest($proxy, ++$retry);
        }

        $responseCode = $browser->getResponse()->getStatusCode();
        if ($responseCode !== 200) {
            dump(sprintf('When parsing the page "%s", the server will return a response "%s"', $this->url, $responseCode));
            return $this->makeProxyRequest($proxy, ++$retry);
        }

        if (!preg_match($this->getReValidation($this->identifier), $browser->getResponse()->getContent())) {
            dump('Not found validation element!');
            return $this->makeProxyRequest($proxy, ++$retry);
        }

        return $browser;
    }

    public function makeProxyDownload($url, $path, $proxy, int $retry = 0): string
    {
        if ($retry > self::MAX_RETRY) {
            $this->proxies->next();
            throw new \LogicException(sprintf(PHP_EOL . 'Max attempt limit for PROXY "%s".', $proxy));
        }

        $browser = new HttpBrowser(
            HttpClient::create(
                array_merge(
                    $this->httpClientParam,
                    [
                        'proxy'        => $proxy,
                        'max_duration' => self::REQUEST_TIMEOUT
                    ]
                )
            )
        );

        try {
            dump(sprintf('Download "%s" with proxy "%s"', $url, $proxy));
            return $this->makeStandardDownload($url, $path);
        } catch (TransportException $exception) {
            dump('Remove proxy');
            $this->proxies->next();
            return $this->makeProxyDownload($url, $path, ++$retry);
        }
    }


    public function makeStandardDownload(string $uri, string $savePath, int $retry = 0): string
    {
        if ($retry > self::MAX_RETRY) {
            throw new \LogicException(sprintf(PHP_EOL . 'Max attempt limit for URL "%s".', $uri));
        }

        $originalUri = $uri;

        $uri = $this->getAbsoluteUri($uri);

        $server = $this->server;

        if (!empty($server['HTTP_HOST']) && null === parse_url($originalUri, \PHP_URL_HOST)) {
            $uri = preg_replace('{^(https?\://)'.preg_quote($this->extractHost($uri)).'}', '${1}'.$server['HTTP_HOST'], $uri);
        }

        if (isset($server['HTTPS']) && null === parse_url($originalUri, \PHP_URL_SCHEME)) {
            $uri = preg_replace('{^'.parse_url($uri, \PHP_URL_SCHEME).'}', $server['HTTPS'] ? 'https' : 'http', $uri);
        }

        if (!isset($server['HTTP_REFERER']) && !$this->history->isEmpty()) {
            $server['HTTP_REFERER'] = $this->history->current()->getUri();
        }

        if (empty($server['HTTP_HOST'])) {
            $server['HTTP_HOST'] = $this->extractHost($uri);
        }

        $server['HTTPS'] = 'https' == parse_url($uri, \PHP_URL_SCHEME);

        $this->internalRequest = new Request(
            $uri,
            'GET',
            [],
            [],
            $this->cookieJar->allValues($uri),
            $server
        );

        $this->request = $this->filterRequest($this->internalRequest);

        try {
            if ($this->insulated) {
                $this->response = $this->doRequestInProcess($this->request);
            } else {
                $this->response = $this->doRequest($this->request);
            }
        } catch (Exception $exception) {
            dump($exception->getMessage());
            dump(sprintf('Restart download, attempt # %s ', $retry));

            return $this->makeStandardDownload($uri, $savePath, ++$retry);
        }

        $this->cookieJar->updateFromResponse($this->response, $uri);

        $status = $this->response->getStatusCode();

        if ($status >= 300 && $status < 400) {
            $this->redirect = $this->response->getHeader('Location');
            dump("redirect...");

            return $this->makeStandardDownload($this->redirect, $savePath, $retry);
        }

        if (is_dir($savePath)) {
            $disposition = $this->response->getHeader('content-disposition');
            preg_match('/filename=\"(?<filename>.*)\"/i', $disposition, $matches);
            $filename = $matches['filename'] ?? basename($uri);
            $savePath .= DIRECTORY_SEPARATOR . $filename;
        }

        file_put_contents($savePath, $this->response->getContent());

        return basename($savePath);
    }

    private function extractHost(string $uri): ?string
    {
        $host = parse_url($uri, \PHP_URL_HOST);

        if ($port = parse_url($uri, \PHP_URL_PORT)) {
            return $host.':'.$port;
        }

        return $host;
    }
}