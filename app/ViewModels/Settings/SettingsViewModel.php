<?php

declare(strict_types=1);

namespace App\ViewModels\Settings;

use App\Enums\TimeFormatEnum;
use App\Models\EntityType;
use App\Models\Template;
use App\Models\User;

/**
 * What the home of the settings shows.
 *
 * Settings are about two things and the screen says so: what belongs to you alone,
 * and what belongs to the account every vault of it is made from. A vault keeps its
 * own settings, on the vault, and is deliberately absent here.
 *
 * A row whose url is null is a screen that has not been built yet. The screen draws
 * it and does not offer it, rather than leaving a hole where somebody will later
 * look for it.
 */
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
     * The three groups of rows, in the order they are read.
     *
     * A row that carries a `method` is something that changes state rather than
     * somewhere to go, so its url is where a form is sent and never an href.
     *
     * @return array<int, array{title: string|null, description: string|null, rows: array<int, array{title: string, description: string, icon: string, iconClass: string, meta: string|null, url: string|null, method?: string}>}>
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
            /* No heading: what it holds is leaving rather than changing, and one
               over two rows would give them more weight than they want. */
            [
                'title' => null,
                'description' => null,
                'rows' => $this->leavingRows(),
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string, iconClass: string, meta: string|null, url: string|null}>
     */
    private function youRows(): array
    {
        return [
            [
                'title' => __('Profile'),
                'description' => __('Your name, the address people see, and the photograph on your avatar.'),
                'icon' => 'profile',
                'iconClass' => 'bg-terracotta-tint text-terracotta',
                'meta' => null,
                'url' => route('settings.profile.index'),
            ],
            [
                'title' => __('Security and access'),
                'description' => __('Your password, your devices and your API keys.'),
                'icon' => 'lock',
                'iconClass' => 'bg-indigo-tint text-indigo',
                'meta' => $this->twoFactorLabel(),
                'url' => route('settings.security.index'),
            ],
            [
                'title' => __('Preferences'),
                'description' => __('Language, dates, numbers and the clock.'),
                'icon' => 'settings',
                'iconClass' => 'bg-forest-tint text-forest',
                'meta' => $this->preferencesLabel(),
                'url' => route('settings.preferences.index'),
            ],
            [
                'title' => __('Notifications'),
                'description' => __('Which reminders reach you by email, and how long before the date they arrive.'),
                'icon' => 'mail',
                'iconClass' => 'bg-plum-tint text-plum',
                'meta' => null,
                'url' => null,
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string, iconClass: string, meta: string|null, url: string|null}>
     */
    private function accountRows(): array
    {
        return [
            [
                'title' => __('Templates'),
                'description' => __('Which sections a person page carries.'),
                'icon' => 'file',
                'iconClass' => 'bg-indigo-tint text-indigo',
                'meta' => $this->templateCount(),
                'url' => route('settings.templates.index'),
            ],
            [
                'title' => __('Entity types'),
                'description' => __('What you can write down beside people.'),
                'icon' => 'organization',
                'iconClass' => 'bg-sky-tint text-sky',
                'meta' => $this->entityTypeCount(),
                'url' => route('settings.entityTypes.index'),
            ],
            [
                'title' => __('Default values'),
                'description' => __('The lists the application offers you: relationship kinds, genders, call reasons, activity types.'),
                'icon' => 'tasks',
                'iconClass' => 'bg-marigold-tint text-marigold-deep',
                'meta' => null,
                'url' => route('settings.valueLists.index'),
            ],
            [
                'title' => __('Logs'),
                'description' => __('What has been done in this account, and every email it has sent.'),
                'icon' => 'timeline',
                'iconClass' => 'bg-forest-tint text-forest',
                'meta' => null,
                'url' => route('settings.logs.index'),
            ],
        ];
    }

    /**
     * @return array<int, array{title: string, description: string, icon: string, iconClass: string, meta: string|null, url: string|null, method?: string}>
     */
    private function leavingRows(): array
    {
        return [
            [
                'title' => __('Export everything'),
                'description' => __('A single file with every vault, person, note and photograph. It is yours to take.'),
                'icon' => 'vault',
                'iconClass' => 'bg-sand text-muted',
                'meta' => null,
                'url' => null,
            ],
            [
                'title' => __('Sign out'),
                'description' => __('You will have to sign in again on this browser.'),
                'icon' => 'key',
                'iconClass' => 'bg-sand text-muted',
                'meta' => null,
                'url' => route('auth.login.destroy'),
                'method' => 'post',
            ],
        ];
    }

    /**
     * Deleting the account is not a row but the last three words of a sentence
     * saying what goes with it, so nobody reaches them without reading the rest.
     */
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

    /**
     * The two of the four preferences somebody notices without opening the
     * screen.
     */
    private function preferencesLabel(): string
    {
        $locale = app()->getLocale();

        if (! array_key_exists($locale, config('monica.locales'))) {
            $locale = config('app.locale');
        }

        $format = $this->user->time_format ?? TimeFormatEnum::TwentyFourHour;

        return config('monica.locales')[$locale]['label'].' · '.__($format->label());
    }

    private function templateCount(): string
    {
        $count = Template::query()->where('account_id', $this->user->account_id)->count();

        return $count === 1
            ? __('1 template')
            : __(':count templates', ['count' => $count]);
    }

    private function entityTypeCount(): string
    {
        $count = EntityType::query()->where('account_id', $this->user->account_id)->count();

        return $count === 1
            ? __('1 type')
            : __(':count types', ['count' => $count]);
    }
}
