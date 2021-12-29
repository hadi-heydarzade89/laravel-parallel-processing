<?php

namespace App\Services\CurlDownloader;

interface DownloadBuilder
{
    public function setUrls(array $urls): DownloadBuilder;

    public function setConfigs(array $configs): void;

    public function setMethods(array $methods): void;

    public function request(): DownloadBuilder;

    public function response(): array;
}
