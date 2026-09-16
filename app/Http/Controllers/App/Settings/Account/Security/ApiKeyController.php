<?php

declare(strict_types=1);

namespace App\Http\Controllers\App\Settings\Account\Security;

use App\Actions\CreateApiKey;
use App\Actions\DestroyApiKey;
use App\Enums\ApiKeyClientEnum;
use App\Enums\ApiKeyScopeEnum;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ApiKeyController extends Controller
{
    public function create(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255'],
            'sensitive_fields' => ['sometimes', 'boolean'],
            'client' => ['sometimes', Rule::enum(ApiKeyClientEnum::class)],
        ]);

        $scopes = ['*'];

        if ((bool) ($validated['sensitive_fields'] ?? false)) {
            $scopes[] = ApiKeyScopeEnum::SensitiveFields->value;
        }

        $apiKey = new CreateApiKey(
            user: $request->user(),
            name: $validated['name'],
            scopes: $scopes,
            client: ApiKeyClientEnum::tryFrom((string) ($validated['client'] ?? '')) ?? ApiKeyClientEnum::Script,
        )->execute()->plainTextToken;

        return redirect()->route('settings.security.index')
            ->with('apiKey', $apiKey)
            ->with('status', __('Your API key is ready.'))
            ->with('status_description', __('Copy it now. It is not shown again.'));
    }

    public function destroy(Request $request, int $apiKey): RedirectResponse
    {
        new DestroyApiKey(
            user: $request->user(),
            apiKeyId: $apiKey,
        )->execute();

        return redirect()->route('settings.security.index')
            ->with('status', __('The API key is revoked.'))
            ->with('status_description', __('Anything still using it stops working.'));
    }
}
