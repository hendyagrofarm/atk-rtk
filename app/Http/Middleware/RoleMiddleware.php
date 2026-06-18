<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Mengecek apakah user yang login memiliki role tertentu.
     *
     * Contoh penggunaan:
     * ->middleware(['auth', 'role:admin'])
     * ->middleware(['auth', 'role:staff'])
     * ->middleware(['auth', 'role:approver'])
     * ->middleware(['auth', 'role:admin,approver'])
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        if (! $request->user()) {
            abort(401);
        }

        if (! in_array($request->user()->role, $roles)) {
            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}