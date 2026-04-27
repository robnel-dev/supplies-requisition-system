<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$roles
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        // 1. Check if user is logged in
        // 2. Check if the user's role is in the allowed list
        if (!$request->user() || !in_array($request->user()->role, $roles)) {
            // If unauthorized, return a 403 Forbidden page
            abort(403, 'You do not have permission to access this page.');
        }

        return $next($request);
    }
}