<?php

namespace App\Actions;

use App\Exceptions\CannotDispenseException;
use App\Exceptions\InsufficientBalanceException;
use App\Models\Account;
use App\Models\Denomination;
use App\Models\Transaction;
use App\Services\BanknoteDispenser;
use Illuminate\Support\Facades\DB;

final class WithdrawAction
{
    public function __construct(private BanknoteDispenser $dispenser) {}

    public function execute(Account $account, int $amount, string $ip): Transaction
    {
        return DB::transaction(function () use ($account, $amount, $ip) {
            $account = Account::whereKey($account->id)->lockForUpdate()->firstOrFail();

            if ($account->balance < $amount) {
                throw new InsufficientBalanceException();
            }

            $denominations = Denomination::where('currency_id', $account->currency_id)
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $inventory = $denominations->pluck('quantity', 'value')->toArray();

            $plan = $this->dispenser->dispense($inventory, $amount);
            if ($plan === null) {
                throw new CannotDispenseException();
            }

            $balanceBefore = $account->balance;
            $account->decrement('balance', $amount);

            $cases = collect($plan)
                ->map(fn($count, $value) => "WHEN value = " . (int) $value . " THEN quantity - " . (int) $count)
                ->implode(' ');

            DB::update("
                UPDATE denominations
                SET quantity = CASE {$cases} ELSE quantity END
                WHERE currency_id = ? AND value IN (" . implode(',', array_map('intval', array_keys($plan))) . ")
            ", [$account->currency_id]);

            return Transaction::create([
                'account_id'      => $account->id,
                'type'            => 'withdrawal',
                'status'          => 'success',
                'amount'          => $amount,
                'balance_before'  => $balanceBefore,
                'balance_after'   => $account->balance,
                'dispensed_notes' => $plan,
                'ip_address'      => $ip,
            ]);
        }, attempts: 3);
    }
}