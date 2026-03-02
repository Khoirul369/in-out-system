<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class GuestUserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (session('user')) {
            return redirect()->route('dashboard');
        }
        return $next($request);
    }
}
