<?php

use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Currency;
use App\Models\Transaction;
use App\Models\User;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\DenominationSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([CurrencySeeder::class, DenominationSeeder::class]);
    $this->user = User::factory()->create();
    Sanctum::actingAs($this->user);
    $this->azn = Currency::where('code', 'AZN')->first();
});

it('uğurla pul çıxarır və balansı yeniləyir', function () {
    $account = Account::factory()->create(['currency_id' => $this->azn->id, 'balance' => 25000]);

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 12500])
        ->assertSuccessful()
        ->assertJsonPath('data.status', 'success')
        ->assertJsonPath('data.balance_after', 12500);

    expect($account->fresh()->balance)->toBe(12500);
});

it('balans çatmadıqda 422 qaytarır', function () {
    $account = Account::factory()->create(['balance' => 5000]);

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 10000])
        ->assertStatus(422)
        ->assertJsonPath('error', 'INSUFFICIENT_BALANCE');
});

it('eyni idempotency key ilə eyni transaction qaytarır', function () {
    $account = Account::factory()->create(['currency_id' => $this->azn->id, 'balance' => 50000]);
    $key = (string) Str::uuid();

    $r1 = $this->withHeaders(['Idempotency-Key' => $key])
        ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 10000]);
    $r2 = $this->withHeaders(['Idempotency-Key' => $key])
        ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 10000]);

    $r1->assertSuccessful();
    $r2->assertSuccessful();

    expect($r1->json('data.id'))->toBe($r2->json('data.id'));
    expect(Transaction::count())->toBe(1);
    expect($account->fresh()->balance)->toBe(40000);
});

it('idempotency key tələb edir', function () {
    $account = Account::factory()->create(['balance' => 25000]);

    $this->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 100])
        ->assertStatus(400)
        ->assertJsonPath('error', 'IDEMPOTENCY_KEY_REQUIRED');
});

it('autentifikasiya tələb edir', function () {
    auth()->forgetGuards();
    $account = Account::factory()->create(['balance' => 25000]);

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 100])
        ->assertStatus(401);
});

it('rate limit-i tətbiq edir', function () {
    $account = Account::factory()->create(['currency_id' => $this->azn->id, 'balance' => 1000000]);

    foreach (range(1, 5) as $_) {
        $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
            ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 100])
            ->assertSuccessful();
    }

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 100])
        ->assertStatus(429);
});

it('audit log yazır', function () {
    $account = Account::factory()->create(['currency_id' => $this->azn->id, 'balance' => 25000]);

    $this->withHeaders(['Idempotency-Key' => Str::uuid()->toString()])
        ->postJson("/api/v1/accounts/{$account->id}/withdraw", ['amount' => 10000])
        ->assertSuccessful();

    expect(AuditLog::where('action', 'withdrawal')->exists())->toBeTrue();
});
