<?php

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'status' => $this->status,
            'amount' => $this->amount,
            'amount_formatted' => format_money($this->amount, $this->account->currency->code),
            'balance_before' => $this->balance_before,
            'balance_after' => $this->balance_after,
            'dispensed' => $this->dispensed_notes,
            'related_transaction_id' => $this->related_transaction_id,
            'duration_ms' => $this->duration_ms,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
