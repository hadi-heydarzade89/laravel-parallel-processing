<?php

namespace App\Console\Commands;

use Amp\Parallel\Worker\DefaultPool;
use App\Models\City;
use App\Services\ASyncDownloadBuilder;
use App\Services\TaskScheduler\Foo;
use App\Services\TaskScheduler\Scheduler;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use function Amp\ParallelFunctions\parallelMap;
use function Amp\Promise\wait;

class Sample extends Command
{

    private $city;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sample';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

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
        $scheduler = new Scheduler;

        /*$scheduler->newTask(task(10));
        $scheduler->newTask(task(5));

        $scheduler->run();*/


        $start = time();
        $startMicro = round(microtime(true) * 1000);
        Log::info('start time ' . date('Y-m-d H:i:s', time()));
        Log::info('before Currently used memory ' . (memory_get_usage() / 1024));
        Log::info('before Peak memory usage ' . (memory_get_peak_usage() / 1024));

        $url = "http://homsa.local/test";
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

        Log::info('after Currently used memory ' . (memory_get_usage() / 1024));
        Log::info('after Peak memory usage ' . (memory_get_peak_usage() / 1024));
        Log::info('duration ' . (time() - $start));
        Log::info('duration micro ' . (round(microtime(true) * 1000) - $startMicro));


    }


    private function makeCity()
    {
        exec('php ' . base_path() . '/artisan test 2>&1');
        yield;
    }


}
