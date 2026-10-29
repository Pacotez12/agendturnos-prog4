<?php

namespace App\Http\Middleware;

use App\Enums\RolUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRol
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! in_array($user->rol?->value, $roles, true)) {
            abort(403);
        }

        if ($user->rol === RolUsuario::Profesional && ! $user->profesional()->activas()->exists()) {
            abort(403);
        }

        return $next($request);
    }
}
