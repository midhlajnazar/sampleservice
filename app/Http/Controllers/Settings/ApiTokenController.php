<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiTokenController extends Controller
{
    /** Abilities a token can be granted, with a description for the UI. */
    public const array ABILITIES = [
        'services:read' => 'List services and their form schemas',
        'requests:read' => 'View your applications and documents',
        'requests:write' => 'Create, cancel and respond to applications',
        'payments:write' => 'Pay for applications',
    ];

    public const array ADMIN_ABILITIES = [
        'admin' => 'Process applications as an administrator',
    ];

    public function index(Request $request): Response
    {
        return Inertia::render('settings/ApiTokens', [
            'tokens' => $request->user()->tokens()->latest()->get()->map(fn (PersonalAccessToken $token) => [
                'id' => $token->id,
                'name' => $token->name,
                'abilities' => $token->abilities,
                'last_used_at' => $token->last_used_at?->diffForHumans(),
                'expires_at' => $token->expires_at?->toFormattedDateString(),
                'created_at' => $token->created_at?->toFormattedDateString(),
            ]),
            'availableAbilities' => $this->abilitiesFor($request),
            'apiBaseUrl' => url('/api/v1'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'abilities' => ['required', 'array', 'min:1'],
            'abilities.*' => [Rule::in(array_keys($this->abilitiesFor($request)))],
            'expires_in_days' => ['nullable', 'integer', Rule::in([7, 30, 90, 365])],
        ]);

        $token = $request->user()->createToken(
            $validated['name'],
            $validated['abilities'],
            isset($validated['expires_in_days']) ? now()->addDays((int) $validated['expires_in_days']) : null,
        );

        // The plain-text token is only shown once.
        Inertia::flash('newToken', $token->plainTextToken);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'API token created. Copy it now; it will not be shown again.']);

        return to_route('api-tokens.index');
    }

    public function destroy(Request $request, int $token): RedirectResponse
    {
        $request->user()->tokens()->whereKey($token)->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'API token revoked.']);

        return to_route('api-tokens.index');
    }

    /**
     * @return array<string, string>
     */
    private function abilitiesFor(Request $request): array
    {
        return self::ABILITIES + ($request->user()->isAdmin() ? self::ADMIN_ABILITIES : []);
    }
}
