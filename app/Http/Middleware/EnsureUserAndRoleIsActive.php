<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserAndRoleIsActive
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $user = Auth::user();

            if (! $user->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'Your account has been deactivated. Please contact the administrator.',
                ]);

                return redirect()->route('login');
            }

            if ($user->role && ! $user->role->is_active) {
                Auth::guard('web')->logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                Inertia::flash('toast', [
                    'type' => 'error',
                    'message' => 'Your role has been deactivated. Please contact the super admin.',
                ]);

                return redirect()->route('login');
            }
        }

        return $next($request);
    }
}
