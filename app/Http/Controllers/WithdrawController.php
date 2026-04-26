<?php

namespace App\Http\Controllers;

use App\Actions\WithdrawAction;
use App\Http\Requests\WithdrawRequest;
use App\Http\Resources\TransactionResource;
use App\Models\Account;

class WithdrawController extends Controller
{
    public function __invoke(WithdrawRequest $request, Account $account, WithdrawAction $action): TransactionResource
    {
        $transaction = $action->execute(
            account: $account,
            amount:  $request->integer('amount'),
            ip:      $request->ip() ?? '0.0.0.0',
        );

        return new TransactionResource($transaction->load('account.currency'));
    }
}