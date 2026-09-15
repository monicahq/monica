<?php

declare(strict_types=1);

namespace App\ViewModels\Settings;

use App\Enums\TimeFormatEnum;
use App\Helpers\AvailableLocales;
use App\Models\EntityType;
use App\Models\RelationshipType;
use App\Models\Template;
use App\Models\User;

class SettingsViewModel
{
    public function __construct(
        private readonly User $user,
    ) {}

    public function user(): User
    {
        return $this->user;
    }

    public function name(): string
    {
        return $this->user->name;
    }

    public function description(): string
    {
        return __('Everything about you and about this account. Vaults keep their own settings, on the vault.');
    }

    public function signedInAs(): string
    {
        return __('Signed in as :name · :email', [
            'name' => $this->user->name,
            'email' => $this->user->email,
        ]);
    }

    /**
     * @return array<int, array{title: string|null, description: string|null, rows: array<int, array{title: string, description: string, icon: string, meta: string|null, url: string|null, method?: string}>}>
     */
    public function sections(): array
    {
        return [
            [
                'title' => __('You'),
                'description' => __('How you sign in, how you are shown, and how the application behaves for you alone.'),
                'rows' => $this->youRows(),
            ],
            [
                'title' => __('This account'),
                'description' => __('What every vault in the account is made of, and the record of what has happened in it.'),
                'rows' => $this->accountRows(),
            ],
            [
                'title' => null,
                'description' => null,
                'rows' => $this->leavingRows(),
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string, meta: string|null, url: string|null}>
     */
    private function youRows(): array
    {
        return [
            [
                'title' => __('Profile'),
                'description' => __('Your name, the address people see, and your photo.'),
                'icon' => 'profile',
                'meta' => null,
                'url' => route('settings.profile.index'),
            ],
            [
                'title' => __('Security and access'),
                'description' => __('Your password, your devices and your API keys.'),
                'icon' => 'lock',
                'meta' => $this->twoFactorLabel(),
                'url' => route('settings.security.index'),
            ],
            [
                'title' => __('Preferences'),
                'description' => __('Language, dates, numbers and the clock.'),
                'icon' => 'settings',
                'meta' => $this->preferencesLabel(),
                'url' => route('settings.preferences.index'),
            ],
            [
                'title' => __('Notifications'),
                'description' => __('Which reminders reach you by email, and how long before the date they arrive.'),
                'icon' => 'mail',
                'meta' => null,
                'url' => null,
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string, meta: string|null, url: string|null}>
     */
    private function accountRows(): array
    {
        return [
            [
                'title' => __('Templates'),
                'description' => __('Which sections a person page carries.'),
                'icon' => 'file',
                'meta' => $this->templateCount(),
                'url' => route('settings.templates.index'),
            ],
            [
                'title' => __('Kinds of record'),
                'description' => __('What you can write down beside people.'),
                'icon' => 'organization',
                'meta' => $this->entityTypeCount(),
                'url' => route('settings.entityTypes.index'),
            ],
            [
                'title' => __('Relationship types'),
                'description' => __('The links you can put between two people.'),
                'icon' => 'relationship',
                'meta' => $this->relationshipTypeCount(),
                'url' => route('settings.relationshipTypes.index'),
            ],
            [
                'title' => __('Value lists'),
                'description' => __('The lists the application offers you: relationship kinds, genders, call reasons, activity types.'),
                'icon' => 'tasks',
                'meta' => null,
                'url' => route('settings.valueLists.index'),
            ],
            [
                'title' => __('Logs'),
                'description' => __('What has been done in this account, and every email it has sent.'),
                'icon' => 'timeline',
                'meta' => null,
                'url' => route('settings.logs.index'),
            ],
            ...(config('monica.self_hosted') ? [] : [[
                'title' => __('Billing'),
                'description' => __('The offer this account is on, and how to change it.'),
                'icon' => 'coin',
                'meta' => null,
                'url' => route('settings.billing.index'),
            ]]),
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string, meta: string|null, url: string|null, method?: string}>
     */
    private function leavingRows(): array
    {
        return [
            [
                'title' => __('Export everything'),
                'description' => __('A single file with every vault, person, note and photograph. It is yours to keep.'),
                'icon' => 'vault',
                'meta' => null,
                'url' => null,
            ],
            [
                'title' => __('Sign out'),
                'description' => __('You will have to sign in again on this browser.'),
                'icon' => 'key',
                'meta' => null,
                'url' => route('auth.login.destroy'),
                'method' => 'post',
            ],
        ];
    }

    public function deleteUrl(): string
    {
        return route('settings.profile.index').'#delete-your-account';
    }

    private function twoFactorLabel(): string
    {
        return $this->user->usesTwoFactorAuthentication()
            ? __('Two-factor on')
            : __('Two-factor off');
    }

    private function preferencesLabel(): string
    {
        $locale = app()->getLocale();

        if (! AvailableLocales::has($locale)) {
            $locale = (string) config('app.locale');
        }

        $format = $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;

        return (string) AvailableLocales::details($locale)['label'].' · '.__($format->label());
    }

    private function templateCount(): string
    {
        $count = Template::query()->where('account_id', $this->user->account_id)->count();

        return trans_choice(':count template|:count templates', $count);
    }

    private function entityTypeCount(): string
    {
        $count = EntityType::query()->where('account_id', $this->user->account_id)->count();

        return trans_choice(':count type|:count types', $count);
    }

    private function relationshipTypeCount(): string
    {
        $count = RelationshipType::query()->where('account_id', $this->user->account_id)->count();

        return trans_choice(':count type|:count types', $count);
    }
}
