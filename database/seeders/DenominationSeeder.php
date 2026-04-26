<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\Denomination;
use Illuminate\Database\Seeder;

class DenominationSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'AZN' => [
                20000 => 50,
                10000 => 100,
                5000 => 100,
                2000 => 200,
                1000 => 200,
                500 => 500,
                100 => 1000,
            ],
            'USD' => [
                10000 => 100,
                5000 => 100,
                2000 => 200,
                1000 => 200,
                500 => 300,
                100 => 500,
            ],
        ];

        foreach ($catalog as $code => $denoms) {
            $currency = Currency::where('code', $code)->firstOrFail();

            foreach ($denoms as $value => $quantity) {
                Denomination::updateOrCreate(
                    ['currency_id' => $currency->id, 'value' => $value],
                    ['quantity' => $quantity],
                );
            }
        }
    }
}
