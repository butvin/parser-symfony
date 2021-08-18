<?php

namespace App\Service;

use Symfony\Component\BrowserKit\Request;
use Symfony\Component\BrowserKit\HttpBrowser as BaseHttpBrowser;

class HttpBrowser extends BaseHttpBrowser
{
    public function download(string $uri, string $savePath, array $server = []): string
    {
        $originalUri = $uri;

        $uri = $this->getAbsoluteUri($uri);

        $server = array_merge($this->server, $server);

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

        if ($this->insulated) {
            $this->response = $this->doRequestInProcess($this->request);
        } else {
            $this->response = $this->doRequest($this->request);
        }

        $this->cookieJar->updateFromResponse($this->response, $uri);

        $status = $this->response->getStatusCode();

        if ($status >= 300 && $status < 400) {
            $this->redirect = $this->response->getHeader('Location');

            return $this->download($this->redirect, $savePath);
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