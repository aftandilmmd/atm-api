<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResponseTime
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = $next($request);
        $ms = (int) ((microtime(true) - $start) * 1000);
        $response->headers->set('X-Response-Time-Ms', (string) $ms);

        return $response;
    }
}
