<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use App\Models\Country;

class CountryFetcherCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'country:fetch';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fetch countries all around the world.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $response = Http::get('https://restcountries.com/v3.1/all?fields=name');
        $countries = $response->json();
        foreach ($countries as $country) {
            Country::create([
                'name'  => $country['name']['common'],
                'official_name' => $country['name']['official'],
                'locale_name' => json_encode([
                    'id' => ''
                ])
            ]);
        }
    }
}
