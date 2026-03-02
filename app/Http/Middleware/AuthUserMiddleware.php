<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;

class AuthUserMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $sessionUserId = session('user.id');
        if (!$sessionUserId) {
            return redirect()->route('login');
        }

        if (!User::where('id', $sessionUserId)->exists()) {
            $request->session()->forget('user');
            return redirect()->route('login')->withErrors([
                'username' => 'Sesi tidak valid. Silakan login ulang.',
            ]);
        }

        return $next($request);
    }
}
