<?php

namespace App\Http\Controllers;

use App\Actions\ReverseTransactionAction;
use App\Http\Resources\TransactionResource;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReverseController extends Controller
{
    public function __invoke(Request $request, Transaction $transaction, ReverseTransactionAction $action): TransactionResource
    {
        Gate::authorize('reverse', $transaction);

        $reversal = $action->execute($transaction, $request->ip() ?? '0.0.0.0');

        return new TransactionResource($reversal->load('account.currency'));
    }
}