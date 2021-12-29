<?php

namespace Database\Seeders;

use App\Models\City;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Log;

class CitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $start = time();
        Log::info('start time ' . date('Y-m-d H:i:s', time()));
        Log::info('before Currently used memory ' . (memory_get_usage() / 1024));
        Log::info('before Peak memory usage ' . (memory_get_peak_usage() / 1024));
        $cities = City::factory()->count(2000000)->make();
        file_put_contents('./s1.json', json_encode($cities));

        Log::info('after Currently used memory ' . (memory_get_usage() / 1024));
        Log::info('after Peak memory usage ' . (memory_get_peak_usage() / 1024));
        Log::info('duration ' . (time() - $start));
    }
}
