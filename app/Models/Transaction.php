<?php

namespace App\Models;

use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $account_id
 * @property string $type
 * @property string $status
 * @property int $amount
 * @property int $balance_before
 * @property int $balance_after
 * @property array<int, int>|null $dispensed_notes
 * @property int|null $related_transaction_id
 * @property string|null $idempotency_key
 * @property string|null $ip_address
 * @property int|null $duration_ms
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property Account $account
 */
#[Fillable([
    'account_id', 'type', 'status', 'amount',
    'balance_before', 'balance_after', 'dispensed_notes',
    'related_transaction_id', 'idempotency_key', 'ip_address', 'duration_ms',
])]
#[WithoutTimestamps] // sadece created_at var
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'dispensed_notes' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Account, $this>
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    /**
     * @param  Builder<Transaction>  $q
     * @return Builder<Transaction>
     */
    public function scopeWithdrawals(Builder $q): Builder
    {
        return $q->where('type', 'withdrawal');
    }

    /**
     * @param  Builder<Transaction>  $q
     * @return Builder<Transaction>
     */
    public function scopeReversals(Builder $q): Builder
    {
        return $q->where('type', 'reversal');
    }
}
