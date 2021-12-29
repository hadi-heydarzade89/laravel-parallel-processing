<?php

namespace App\Console\Commands;

use App\Models\City;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\LazyCollection;

class test extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test';

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
        /*$x = LazyCollection::times(1000 * 1000 * 1000)
            ->filter(fn ($number) => $number % 2 == 0)
            ->take(1000);

        dd($x);*/
        $allNumbersGenerator = generate_numbers();
        $logins = LazyCollection::times(1000000, fn () => [
            'user_id' => 24,
            'name' => 'Houdini',
            'logged_in_at' => now()->toIsoString(),
        ]);

        dd(json_encode($logins));
    }
}
