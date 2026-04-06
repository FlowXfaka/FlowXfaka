<?php

namespace App\Http\Middleware;

use App\Support\InstallLock;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureApplicationInstalled
{
    public function handle(Request $request, Closure $next): Response
    {
        if (InstallLock::exists()) {
            return $next($request);
        }

        config([
            'session.driver' => 'file',
            'cache.default' => 'file',
            'queue.default' => 'sync',
        ]);

        if (
            $request->is('/')
            || $request->is('install')
            || $request->is('install/*')
            || $request->is('up')
        ) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Application is not installed.',
                'install_url' => url('/'),
            ], 503);
        }

        return redirect('/');
    }
}
