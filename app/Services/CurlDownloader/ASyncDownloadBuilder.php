<?php

namespace App\Services\CurlDownloader;


use GuzzleHttp\Client;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Log;

class ASyncDownloadBuilder implements DownloadBuilder
{

    protected array $urls = [];

    protected array $configs = [];

    protected array $methods = [];

    protected array $responses = [];

    protected array $unsolvedUrl = [];

    protected Client $client;

    protected int $timeout;

    public function __construct($timeout = 7)
    {
        $this->timeout = $timeout;
    }

    public function setUrls(array $urls): DownloadBuilder
    {
        $this->client = new Client([
            'verify' => false,
            'timeout' => $this->timeout
        ]);

        Log::info('client obj ' . json_encode($this->client));
        Log::info('public function setUrls(array $urls): DownloadBuilder start');
        $this->setConfigs($urls);
        $this->setMethods($urls);
        Log::info('public function setUrls(array $urls): DownloadBuilder before loop');
        foreach ($urls as $key => $url) {
            if (isset($url[0])) {
                $this->urls[$key] = $url[0];
                Log::info('public function setUrls(array $urls): DownloadBuilder in loop url ' . json_encode($url));
            }
        }
        Log::info('public function setUrls(array $urls): DownloadBuilder end');
        return $this;
    }

    public function setMethods(array $methods): void
    {

        Log::info('public function setMethods(array $methods): void start');
        foreach ($methods as $key => $method) {
            $this->methods[$key] = 'GET';
            if (!empty($method[1])) {
                $this->methods[$key] = $method[1];
            }
        }

        Log::info('public function setMethods(array $methods): void end');
    }

    public function request(): DownloadBuilder
    {
        Log::info('public function request(): DownloadBuilder start');

        $urls = $this->urls;
        $methods = $this->methods;
        Log::info('public function request(): DownloadBuilder before requests');
        $requests = function () use ($urls, $methods) {

            foreach ($urls as $key => $url) {
                try {
                    Log::info('public function request(): DownloadBuilder before new request');
                    yield new Request($methods[$key], $url, $this->configs[$key]);
                } catch (RequestException  $exception) {
                    Log::critical(
                        'Request exception in public function request(): after Request Class ' . $exception->getMessage()
                    );
                }
            }
        };

        try {
            $pool = new Pool($this->client, $requests(), [
                'concurrency' => 100,
                'fulfilled' => function (Response $response, $index) {
                    $this->responses[$index] = $response->getBody();
                    Log::info('public function request(): DownloadBuilder start index is ' . date("H:i:s"), [$index]);
                },
                'rejected' => function ($reason, $index) {
                    /*$this->unsolvedUrl[$index] = [$this->urls[$index]];
                    if ($reason instanceof RequestException) {
                        sleep(3);
                        Log::critical('$pool = new Pool() RequestException ' . $index . ' ' . $reason->getMessage());
                    } elseif ($reason instanceof ConnectException) {
                        sleep(5);
                        Log::critical('$pool = new Pool() ConnectException ' . $index . ' ' . $reason->getMessage());
                    } else {
                        Log::critical('$pool = new Pool() else Exception ' . $index . ' ' . $reason->getMessage());
                    }*/
                },
            ]);
        } catch (\Exception $exception) {
            Log::critical(
                'Request exception in public function request(): after Pool Class ' . $exception->getMessage()
            );
        }
        Log::info('public function request(): DownloadBuilder before promise');
        $promise = $pool->promise();
        Log::info('public function request(): DownloadBuilder before wait');
        $promise->wait(false);

        if (count($this->unsolvedUrl) > 0) {
            $unsolvedUrl = $this->unsolvedUrl;
            $this->unsolvedUrl = [];
            $this->timeout = ($this->timeout + 2);
            Log::info('timeout ' . $this->timeout);
            $this->setUrls($unsolvedUrl)->request();
        }
        Log::info('public function request(): DownloadBuilder end');
        return $this;
    }

    public function response(): array
    {
        Log::info('public function response(): array ');

        return $this->responses;
    }

    public function setConfigs(array $configs): void
    {

        Log::info('public function setConfigs(array $configs): void start');

        foreach ($configs as $key => $config) {
            $this->configs[$key] = [];
            if (isset($config[2]) && count($config[2]) > 0) {
                $this->configs[$key] = $config[2];

            } else {
                $this->configs[$key] = [
                    'timeout' => 0.000001,
                    'headers' => [
                        'Accept-Encoding' => 'gzip, deflate',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,*/*;q=0.8',
                    ]
                ];
            }
        }
        Log::info('public function setConfigs(array $configs): void end');
    }
}
