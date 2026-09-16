<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        abort_unless($user && in_array($user->role, ['admin','judge'], true), 403, 'Không có quyền quản trị.');
        return $next($request);
    }
}
