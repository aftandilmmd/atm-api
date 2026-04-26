<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Currency;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        $azn = Currency::where('code', 'AZN')->firstOrFail();
        $usd = Currency::where('code', 'USD')->firstOrFail();
        $customer = User::where('email', 'customer@atm.test')->first();

        Account::firstOrCreate(
            ['owner_name' => 'Test Customer 1'],
            ['currency_id' => $azn->id, 'user_id' => $customer?->id, 'balance' => 250000],
        );

        Account::firstOrCreate(
            ['owner_name' => 'Test Customer 2'],
            ['currency_id' => $azn->id, 'balance' => 1000000],
        );

        Account::firstOrCreate(
            ['owner_name' => 'Test Customer USD'],
            ['currency_id' => $usd->id, 'user_id' => $customer?->id, 'balance' => 500000],
        );
    }
}