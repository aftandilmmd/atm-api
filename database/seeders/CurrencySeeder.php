<?php

namespace Database\Seeders;

use App\Models\Currency;
use Illuminate\Database\Seeder;

class CurrencySeeder extends Seeder
{
    public function run(): void
    {
        Currency::firstOrCreate(['code' => 'AZN'], ['name' => 'Azerbaijani Manat']);
        Currency::firstOrCreate(['code' => 'USD'], ['name' => 'US Dollar']);
    }
}