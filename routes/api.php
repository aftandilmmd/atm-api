<?php

use App\Http\Controllers\AccountController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\WithdrawController;
use App\Http\Middleware\RequireIdempotencyKey;
use Illuminate\Support\Facades\Route;

Route::middleware([RequireIdempotencyKey::class])->prefix('v1')->group(function () {
    Route::get('accounts', [AccountController::class, 'index']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);
    Route::get('accounts/{account}/transactions', [TransactionController::class, 'forAccount']);
    Route::get('transactions/{transaction}', [TransactionController::class, 'show']);
    Route::post('accounts/{account}/withdraw', WithdrawController::class);
});