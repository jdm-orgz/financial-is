<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckCasbinPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $menu, string $action = '*'): Response
    {
        if (! $request->user()->hasPermissionTo($menu, $action)) {
            abort(403, 'Unauthorized access.');
        }

        return $next($request);
    }
}
