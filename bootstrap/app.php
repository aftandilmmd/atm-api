<?php

use App\Exceptions\CannotDispenseException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\TransactionAlreadyReversedException;
use App\Http\Middleware\ResponseTime;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->trustProxies(at: '*');
        $middleware->api(append: [ResponseTime::class]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->render(function (InsufficientBalanceException $e, Request $req) {
            return response()->json(['error' => 'INSUFFICIENT_BALANCE', 'message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (CannotDispenseException $e, Request $req) {
            return response()->json(['error' => 'CANNOT_DISPENSE', 'message' => $e->getMessage()], 422);
        });

        $exceptions->render(function (TransactionAlreadyReversedException $e, Request $req) {
            return response()->json(['error' => 'TRANSACTION_ALREADY_REVERSED', 'message' => $e->getMessage()], 422);
        });
    })->create();
