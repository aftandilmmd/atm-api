<?php

namespace App\Http\Controllers;

use App\Http\Resources\TransactionResource;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class TransactionController extends Controller
{
    public function show(Transaction $transaction): TransactionResource
    {
        return new TransactionResource($transaction->load('account.currency'));
    }

    public function forAccount(Account $account): AnonymousResourceCollection
    {
        return TransactionResource::collection(
            $account->transactions()->latest('created_at')->paginate(20)
        );
    }
}