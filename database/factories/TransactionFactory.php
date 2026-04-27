<?php

namespace Database\Factories;

use App\Enums\TransactionStatus;
use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    protected $model = Transaction::class;

    public function definition(): array
    {
        return [
            'account_id' => Account::factory(),
            'type' => TransactionType::WITHDRAW,
            'status' => TransactionStatus::SUCCESS,
            'amount' => 10000,
            'balance_before' => 50000,
            'balance_after' => 40000,
            'created_at' => now(),
        ];
    }
}
