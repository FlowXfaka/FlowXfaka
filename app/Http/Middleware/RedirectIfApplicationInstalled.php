<?php

namespace App\Http\Middleware;

use App\Support\InstallLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfApplicationInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallLock::exists()) {
            return redirect('/');
        }

        return $next($request);
    }
}
