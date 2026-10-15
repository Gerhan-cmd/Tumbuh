<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureOnboarded
{
    /**
     * Pengguna yang belum mengatur target diarahkan ke halaman onboarding.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->hasOnboarded()) {
            return redirect()->route('onboarding.show');
        }

        return $next($request);
    }
}
