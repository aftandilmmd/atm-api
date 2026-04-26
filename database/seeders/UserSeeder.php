<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(['email' => 'admin@atm.test'], [
            'name' => 'Admin', 'password' => Hash::make('password'), 'role' => 'admin',
        ]);
        User::firstOrCreate(['email' => 'supervisor@atm.test'], [
            'name' => 'Supervisor', 'password' => Hash::make('password'), 'role' => 'supervisor',
        ]);
        User::firstOrCreate(['email' => 'customer@atm.test'], [
            'name' => 'Customer', 'password' => Hash::make('password'), 'role' => 'customer',
        ]);
    }
}
