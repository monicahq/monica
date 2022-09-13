<?php

namespace App\Actions\Jetstream;

use App\Vault\ManageVault\Web\ViewHelpers\VaultIndexViewHelper;
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
        $providers = collect(config('auth.login_providers'))->filter(fn ($provider) => ! empty($provider));
        $providersName = $providers->mapWithKeys(function ($provider) {
            return [
                $provider => config("services.$provider.name") ?? __("auth.login_provider_{$provider}"),
            ];
        });

        $webauthnKeys = $request->user()->webauthnKeys()
            ->get()
            ->map(function ($key) {
                return [
                    'id' => $key->id,
                    'name' => $key->name,
                    'type' => $key->type,
                    'last_active' => $key->updated_at->diffForHumans(),
                ];
            })
            ->toArray();

        $data['providers'] = $providers;
        $data['providersName'] = $providersName;
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
