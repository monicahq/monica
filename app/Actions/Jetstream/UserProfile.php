<?php

namespace App\Actions\Jetstream;

use App\Domains\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
use Illuminate\Http\Request;

class UserProfile
{
    /**
     * Get user profile data.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  array  $data
     * @return array
     */
    public function __invoke(Request $request, array $data): array
    {
        $providers = collect(config('auth.login_providers'))
            ->filter(fn ($provider) => ! empty($provider))
            ->mapWithKeys(fn ($provider) => [
                $provider => [
                    'name' => config("services.$provider.name") ?? __("auth.login_provider_{$provider}"),
                    'logo' => config("services.$provider.logo") ?? "/img/auth/$provider.svg",
                ],
            ]);

        $webauthnKeys = $request->user()->webauthnKeys
            ->map(fn ($key) => [
                'id' => $key->id,
                'name' => $key->name,
                'type' => $key->type,
                'last_active' => $key->updated_at->diffForHumans(),
            ])
            ->toArray();

        $data['providers'] = $providers;
        $data['userTokens'] = $request->user()->userTokens()->get();
        $data['webauthnKeys'] = $webauthnKeys;

        $data['locales'] = collect(config('lang-detector.languages'))->map(fn ($locale) => [
            'id' => $locale,
            'name' => __('auth.lang', [], $locale),
        ]);

        $data['layoutData'] = VaultIndexViewHelper::layoutData();

        return $data;
    }
}
