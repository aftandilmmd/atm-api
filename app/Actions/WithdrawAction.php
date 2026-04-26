<?php

namespace App\Actions;

use App\Exceptions\CannotDispenseException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Denomination;
use App\Models\Transaction;
use App\Services\BanknoteDispenser;
use Illuminate\Support\Facades\DB;

final class WithdrawAction
{
    public function __construct(private BanknoteDispenser $dispenser) {}

    public function execute(Account $account, int $amount, ?string $idempotencyKey, string $ip): Transaction
    {
        if ($idempotencyKey !== null) {
            $existing = $this->findByIdempotencyKey($idempotencyKey);

            if ($existing !== null) {
                return $existing;
            }
        }

        $started = microtime(true);

        $transaction = DB::transaction(function () use ($account, $amount, $idempotencyKey, $ip) {
            $account = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();

            if ($account->balance < $amount) {
                throw new InsufficientBalanceException;
            }

            $denominations = Denomination::where('currency_id', $account->currency_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $inventory = $denominations->pluck('quantity', 'value')->toArray();

            $plan = $this->dispenser->dispense($inventory, $amount);
            if ($plan === null) {
                throw new CannotDispenseException;
            }

            $balanceBefore = $account->balance;
            $account->decrement('balance', $amount);

            $cases = collect($plan)
                ->map(fn ($count, $value) => 'WHEN value = '.(int) $value.' THEN quantity - '.(int) $count)
                ->implode(' ');

            DB::update("
                UPDATE denominations
                SET quantity = CASE {$cases} ELSE quantity END
                WHERE currency_id = ? AND value IN (".implode(',', array_map('intval', array_keys($plan))).')
            ', [$account->currency_id]);

            $transaction = Transaction::create([
                'account_id' => $account->id,
                'type' => 'withdrawal',
                'status' => 'success',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $account->balance,
                'dispensed_notes' => $plan,
                'idempotency_key' => $idempotencyKey,
                'ip_address' => $ip,
            ]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'withdrawal',
                'entity_type' => Transaction::class,
                'entity_id' => $transaction->id,
                'changes' => [
                    'balance_before' => $balanceBefore,
                    'balance_after' => $account->balance,
                    'dispensed' => $plan,
                ],
                'ip_address' => $ip,
            ]);

            return $transaction;
        }, attempts: 3);

        $duration = (int) ((microtime(true) - $started) * 1000);
        Transaction::whereKey($transaction->id)->update(['duration_ms' => $duration]);
        $transaction->duration_ms = $duration;

        if ($idempotencyKey !== null) {
            $this->storeIdempotency($idempotencyKey, $transaction);
        }

        return $transaction;
    }

    private function findByIdempotencyKey(string $key): ?Transaction
    {
        $row = DB::table('idempotency_keys')
            ->where('key', $key)
            ->where('expires_at', '>', now())
            ->first();

        return $row ? Transaction::find($row->transaction_id) : null;
    }

    private function storeIdempotency(string $key, Transaction $transaction): void
    {
        DB::table('idempotency_keys')->insertOrIgnore([
            'key' => $key,
            'transaction_id' => $transaction->id,
            'response' => json_encode(['transaction_id' => $transaction->id]),
            'expires_at' => now()->addHours(24),
            'created_at' => now(),
        ]);
    }
}
