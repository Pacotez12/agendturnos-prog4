<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\View\View;

class NewPasswordController extends Controller
{
    /**
     * Display the password reset view.
     */
    public function create(Request $request, string $token): View
    {
        return view('auth.restablecer-contrasena', [
            'request' => $request,
            'token' => $token,
        ]);
    }

    /**
     * Handle an incoming new password request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:8'],
        ]);

        $credentials = $request->only('email', 'password', 'password_confirmation', 'token');

        $callback = function (User $user, string $password) {
            DB::transaction(function () use ($user, $password) {
                User::whereKey($user->id)->lockForUpdate()->firstOrFail();

                $user->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();

                Password::broker('users')->deleteToken($user);
                Password::broker('invitaciones')->deleteToken($user);
            });

            event(new PasswordReset($user));
        };

        $status = Password::reset($credentials, $callback);

        if ($status !== Password::PASSWORD_RESET) {
            $status = Password::broker('invitaciones')->reset($credentials, $callback);
        }

        if ($status === Password::PASSWORD_RESET) {
            return redirect()
                ->route('login')
                ->with('success', 'Tu contraseña fue actualizada.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'El enlace no es válido o ya venció. Pedí uno nuevo.']);
    }
}
