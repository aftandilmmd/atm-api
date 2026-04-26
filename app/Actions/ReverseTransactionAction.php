<?php

namespace App\Actions;

use App\Exceptions\TransactionAlreadyReversedException;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Denomination;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;

final class ReverseTransactionAction
{
    public function execute(Transaction $original, string $ip): Transaction
    {
        if ($original->type !== 'withdrawal') {
            throw new TransactionAlreadyReversedException(__('Yalnız pul çıxarış əməliyyatları geri alına bilər.'));
        }

        $alreadyReversed = Transaction::where('related_transaction_id', $original->id)
            ->where('type', 'reversal')
            ->exists();

        if ($alreadyReversed) {
            throw new TransactionAlreadyReversedException();
        }

        return DB::transaction(function () use ($original, $ip) {
            $account = Account::whereKey($original->account_id)->lockForUpdate()->firstOrFail();

            $balanceBefore = $account->balance;
            $account->increment('balance', $original->amount);

            foreach ($original->dispensed_notes ?? [] as $value => $count) {
                Denomination::where('currency_id', $account->currency_id)
                    ->where('value', $value)
                    ->increment('quantity', $count);
            }

            $reversal = Transaction::create([
                'account_id'             => $account->id,
                'type'                   => 'reversal',
                'status'                 => 'success',
                'amount'                 => $original->amount,
                'balance_before'         => $balanceBefore,
                'balance_after'          => $account->balance,
                'related_transaction_id' => $original->id,
                'ip_address'             => $ip,
            ]);

            AuditLog::create([
                'user_id'     => auth()->id(),
                'action'      => 'reversal',
                'entity_type' => Transaction::class,
                'entity_id'   => $reversal->id,
                'changes'     => ['original_id' => $original->id, 'amount' => $original->amount],
                'ip_address'  => $ip,
            ]);

            return $reversal;
        }, attempts: 3);
    }
}