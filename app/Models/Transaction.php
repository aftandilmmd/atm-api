<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\WithoutTimestamps;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'account_id', 'type', 'status', 'amount',
    'balance_before', 'balance_after', 'dispensed_notes',
    'related_transaction_id', 'idempotency_key', 'ip_address', 'duration_ms',
])]
#[WithoutTimestamps] // sadece created_at var
class Transaction extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'dispensed_notes' => 'array',
            'created_at'      => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeWithdrawals($q)
    {
        return $q->where('type', 'withdrawal');
    }

    public function scopeReversals($q)
    {
        return $q->where('type', 'reversal');
    }
}