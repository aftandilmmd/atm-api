<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RequireIdempotencyKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $key = $request->header('Idempotency-Key');

        if (! $key || ! Str::isUuid($key)) {
            return response()->json([
                'error'   => 'IDEMPOTENCY_KEY_REQUIRED',
                'message' => __('Idempotency-Key başlığı (UUID) tələb olunur.'),
            ], 400);
        }

        return $next($request);
    }
}