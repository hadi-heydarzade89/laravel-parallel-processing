<?php

namespace App\Console\Commands;

use App\Services\CurlDownloader\ASyncDownloadBuilder;
use Illuminate\Console\Command;

class AsyncCurlRequestCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'parallel:curl {type=sendByGuzzle}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Send async curl request';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     */
    public function handle()
    {
        switch ($this->argument('type')) {
            case 'sendByGuzzle':
                $this->sendByGuzzle();
                break;
            case 'sendRequestByCurl':
                $this->sendRequestByCurl();
                break;
        }
    }

    private function sendByGuzzle()
    {
        $client = new ASyncDownloadBuilder();
        $urls = [];
        for ($i = 0; $i < 200; $i++) {
            $urls[] = ["http://localhost:8080/curl-request"];
        }
        $client->setUrls($urls)->request()->response();
    }

    private function sendRequestByCurl()
    {
        $url = "http://localhost:8080/curl-request";
        $mh = curl_multi_init();
        $handles = array();
        $process_count = 200;
        while ($process_count--) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 30);
            curl_multi_add_handle($mh, $ch);
            $handles[] = $ch;
        }
        $running = null;
        do {
            curl_multi_exec($mh, $running);
        } while ($running > 0);
        for ($i = 0; $i < count($handles); $i++) {
            $out = curl_multi_getcontent($handles[$i]);
            print $out . "\r\n";
            curl_multi_remove_handle($mh, $handles[$i]);
        }
        curl_multi_close($mh);
    }
}
