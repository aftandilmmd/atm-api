<?php

use App\Actions\WithdrawAction;
use App\Models\Account;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\DenominationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CurrencySeeder::class, DenominationSeeder::class]);
});

it('supervisor uğurla geri alır', function () {
    $supervisor = User::factory()->create(['role' => 'supervisor']);
    Sanctum::actingAs($supervisor);

    $account = Account::factory()->create(['balance' => 25000]);
    $original = app(WithdrawAction::class)->execute($account, 10000, null, '127.0.0.1');

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/transactions/{$original->id}/reverse")
        ->assertCreated()
        ->assertJsonPath('data.type', 'reversal');

    expect($account->fresh()->balance)->toBe(25000);
});

it('müştəri rolu icazə almır', function () {
    $customer = User::factory()->create(['role' => 'customer']);
    Sanctum::actingAs($customer);

    $account = Account::factory()->create(['balance' => 25000]);
    $original = app(WithdrawAction::class)->execute($account, 10000, null, '127.0.0.1');

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/transactions/{$original->id}/reverse")
        ->assertStatus(403);
});

it('iki dəfə geri ala bilməz', function () {
    $supervisor = User::factory()->create(['role' => 'supervisor']);
    Sanctum::actingAs($supervisor);

    $account = Account::factory()->create(['balance' => 25000]);
    $original = app(WithdrawAction::class)->execute($account, 10000, null, '127.0.0.1');

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/transactions/{$original->id}/reverse")
        ->assertCreated();

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/transactions/{$original->id}/reverse")
        ->assertStatus(422);
});
