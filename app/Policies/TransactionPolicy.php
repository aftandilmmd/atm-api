<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    public function reverse(User $user, Transaction $transaction): bool
    {
        if (! $user->isSupervisor()) {
            return false;
        }

        if ($transaction->type !== 'withdrawal') {
            return false;
        }

        if ($transaction->created_at->lt(now()->subHours(24))) {
            return false;
        }

        return true;
    }
}
