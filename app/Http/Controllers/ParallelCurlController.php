<?php

namespace App\Http\Controllers;

use App\Models\City;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ParallelCurlController extends Controller
{
    public function __invoke(Request $request)
    {
        Log::info('ip ', [
            $request->ip(),
            $request->getClientIp()
        ]);

        Log::info('before ');
        City::insert(json_decode(json_encode(City::factory()->count(10000)->make()), true));
        Log::info('after ');
        return 'dfkjhdsf';
    }
}
