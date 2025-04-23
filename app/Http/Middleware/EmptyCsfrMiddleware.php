<?php

namespace App\Http\Middleware;

use Closure;

class EmptyCsrfMiddleware
{
    public function handle($request, Closure $next)
    {
        return $next($request);
    }
}
