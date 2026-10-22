<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Settings\ApiTokenController;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Exchange email + password for a personal access token (mobile / server clients).
     */
    public function token(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', 'max:100'],
            'abilities' => ['nullable', 'array'],
            'abilities.*' => [Rule::in(array_keys(ApiTokenController::ABILITIES + ApiTokenController::ADMIN_ABILITIES))],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            throw ValidationException::withMessages(['email' => __('auth.failed')]);
        }

        $abilities = $validated['abilities'] ?? array_keys(ApiTokenController::ABILITIES + ($user->isAdmin() ? ApiTokenController::ADMIN_ABILITIES : []));

        if (in_array('admin', $abilities, true) && ! $user->isAdmin()) {
            throw ValidationException::withMessages(['abilities' => 'Only administrators can request the admin ability.']);
        }

        $token = $user->createToken($validated['device_name'], $abilities);

        return response()->json([
            'token' => $token->plainTextToken,
            'token_type' => 'Bearer',
            'abilities' => $abilities,
            'user' => $user->only('id', 'name', 'email', 'role'),
        ], 201);
    }

    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                ...$user->only('id', 'name', 'email', 'phone', 'emirates_id', 'nationality'),
                'role' => $user->role->value,
                'token_abilities' => $user->currentAccessToken()->abilities ?? ['*'],
            ],
        ]);
    }

    /** Revoke the token used for this request. */
    public function revoke(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Token revoked.']);
    }
}
