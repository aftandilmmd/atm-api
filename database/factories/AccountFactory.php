<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\Currency;
use Illuminate\Database\Eloquent\Factories\Factory;

class AccountFactory extends Factory
{
    protected $model = Account::class;

    public function definition(): array
    {
        return [
            'currency_id' => fn () => Currency::firstOrCreate(
                ['code' => 'AZN'],
                ['name' => 'Azerbaijani Manat']
            )->id,
            'owner_name' => fake()->name(),
            'balance' => 0,
        ];
    }

    public function withBalance(int $amount): self
    {
        return $this->state(['balance' => $amount]);
    }
}
