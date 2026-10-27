<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $userRole = strtolower($request->user()?->role ?? '');
        $allowed = array_map('strtolower', $roles);

        abort_unless(in_array($userRole, $allowed, true), 403);

        return $next($request);
    }
}
