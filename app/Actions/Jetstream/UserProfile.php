<?php

namespace App\Actions\Jetstream;

use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use App\Models\WebauthnKey;
use Illuminate\Http\Request;

class UserProfile
{
    /**
     * Get user profile data.
     */
    public function __invoke(Request $request, array $data): array
    {
        $providers = collect(config('auth.login_providers'))
            ->filter(fn ($provider) => ! empty($provider))
            ->mapWithKeys(fn ($provider) => [
                $provider => [
                    'name' => config("services.$provider.name") ?? trans_ignore("auth.login_provider_{$provider}"),
                    'logo' => config("services.$provider.logo") ?? "/img/auth/$provider.svg",
                ],
            ]);

        $webauthnKeys = $request->user()->webauthnKeys
            ->map(fn (WebauthnKey $key) => [ // @phpstan-ignore-line
                'id' => $key->id,
                'name' => $key->name,
                'type' => $key->type,
                'last_used' => optional($key->used_at)->diffForHumans(),
            ])
            ->toArray();

        $data['providers'] = $providers;
        $data['userTokens'] = $request->user()->userTokens()->get();
        $data['webauthnKeys'] = $webauthnKeys;

        $data['locales'] = collect(config('localizer.supported_locales'))->map(fn (string $locale) => [
            'id' => $locale,
            'name' => __('auth.lang', [], $locale),
        ])->sortByCollator('name');

        $data['layoutData'] = VaultIndexViewHelper::layoutData();

        return $data;
    }
}
