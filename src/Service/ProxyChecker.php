<?php

namespace App\Service;

use Symfony\Component\HttpKernel\DataCollector\DumpDataCollector;

class ProxyChecker
{
    private const REQUEST_TIMEOUT = 5;

    private const CHUNK_SIZE = 100;

    private const GET_MY_REAL_IP_URL = 'https://api.ipify.org/?format=json';

    private string $proxyCheckUrl;

    private string $proxyFilePath;

    public function __construct(string $proxyCheckUrl, string $proxyFilePath)
    {
        $this->proxyCheckUrl = $proxyCheckUrl;
        $this->proxyFilePath = $proxyFilePath;
    }

    public function getSecureProxies(): \Iterator
    {
        $myIp = $this->getMyRealIpAddress();
        $proxies = explode("\n", trim(file_get_contents($this->proxyFilePath)));

        foreach (array_chunk($proxies, self::CHUNK_SIZE) as $key => $proxyChunk) {
            $result = $this->request($this->proxyCheckUrl, $proxyChunk);

            foreach ($result as $proxy => $response) {
                json_decode($response, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }

                if (strpos($response, $myIp) !== false) {
                    continue;
                }

                yield $proxy;
            }
        }

        throw new \RuntimeException('Proxies empty');
    }

    private function getMyRealIpAddress(): string
    {
        try{
            $response = file_get_contents(self::GET_MY_REAL_IP_URL);
        }catch (\Exception $exception){
            dump("Get REAL IP");
            return $this->getMyRealIpAddress();
        }

        $response = json_decode($response, true);

        return $response['ip'];
    }

    private function request(string $url, array $proxies): array
    {
        $curly = [];
        $mh = curl_multi_init();

        foreach ($proxies as $proxy) {
            $handle = curl_init();
            curl_setopt($handle, CURLOPT_URL, $url);
            curl_setopt($handle, CURLOPT_PROXY, $proxy);
            curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($handle, CURLOPT_ENCODING, 'UTF-8');
            curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($handle, CURLOPT_CONNECTTIMEOUT, self::REQUEST_TIMEOUT);
            curl_setopt($handle, CURLOPT_TIMEOUT, self::REQUEST_TIMEOUT);
            curl_multi_add_handle($mh, $handle);

            $curly[] = ['proxy' => $proxy, 'handle' => $handle];
        }

        $running = null;
        do { curl_multi_exec($mh, $running); } while($running > 0);

        $result = [];
        foreach($curly as $meta) {
            $response = curl_multi_getcontent($meta['handle']);
            curl_multi_remove_handle($mh, $meta['handle']);

            $result[$meta['proxy']] = $response;
        }

        return $result;
    }
}