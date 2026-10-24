<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\v1\CrearTokenRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Crear token de acceso personal.
     *
     * Autentica al usuario mediante correo y contraseña para emitir un token Bearer (Sanctum) junto con la información básica del usuario.
     *
     * @unauthenticated
     */
    public function crearToken(CrearTokenRequest $request): JsonResponse
    {
        $user = User::where('email', strtolower(trim($request->input('email'))))->first();

        if (! $user || ! Hash::check($request->input('password'), $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales proporcionadas son incorrectas.'],
            ]);
        }

        $token = $user->createToken($request->input('nombre_dispositivo'))->plainTextToken;

        return response()->json([
            'token' => $token,
            'token_type' => 'Bearer',
            'usuario' => [
                'id' => $user->id,
                'rol' => $user->rol->value,
            ],
        ]);
    }

    /**
     * Revocar token de acceso actual.
     *
     * Invalida y elimina el token Bearer utilizado para autenticar la petición actual, cerrando la sesión API.
     */
    public function revocarToken(Request $request): Response
    {
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }
}
