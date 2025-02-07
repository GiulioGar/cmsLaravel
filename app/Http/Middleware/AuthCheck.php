<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class AuthCheck
{
    public function handle(Request $request, Closure $next)
    {
        // Verifica se l'utente è loggato
        if (!$request->session()->has('user')) {
            return redirect()->route('login');
        }
        return $next($request);
    }
}
