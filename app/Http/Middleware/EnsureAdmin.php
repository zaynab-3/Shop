<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->user()?->hasAnyRole(['main_admin', 'product_admin', 'editor', 'order_manager'])) {
            abort(403);
        }

        return $next($request);
    }
}
