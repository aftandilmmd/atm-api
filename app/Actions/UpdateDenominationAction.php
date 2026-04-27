<?php

namespace App\Actions;

use App\Models\AuditLog;
use App\Models\Denomination;
use Illuminate\Support\Facades\DB;

final class UpdateDenominationAction
{
    public function execute(Denomination $denomination, int $quantity, string $ip): Denomination
    {
        return DB::transaction(function () use ($denomination, $quantity, $ip) {
            $denomination = Denomination::whereKey($denomination->id)->lockForUpdate()->firstOrFail();
            $oldQuantity = $denomination->quantity;
            $denomination->update(['quantity' => $quantity]);

            AuditLog::create([
                'user_id' => auth()->id(),
                'action' => 'denomination.updated',
                'entity_type' => Denomination::class,
                'entity_id' => $denomination->id,
                'changes' => ['old' => $oldQuantity, 'new' => $quantity],
                'ip_address' => $ip,
            ]);

            return $denomination;
        });
    }
}
