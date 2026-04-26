<?php

namespace Database\Factories;

use App\Models\Currency;
use App\Models\Denomination;
use Illuminate\Database\Eloquent\Factories\Factory;

class DenominationFactory extends Factory
{
    protected $model = Denomination::class;

    public function definition(): array
    {
        return [
            'currency_id' => Currency::factory(),
            'value' => 10000,
            'quantity' => 100,
        ];
    }
}
