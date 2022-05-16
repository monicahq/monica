<?php

namespace App\Vault\ManageVaultSettings\Web\ViewHelpers;

use App\Helpers\VaultHelper;
use App\Models\Label;
use App\Models\User;
use App\Models\Vault;
use App\Vault\ManageVaultImportantDateTypes\Web\ViewHelpers\VaultImportantDateTypesViewHelper;

class VaultSettingsIndexViewHelper
{
    public static function data(Vault $vault): array
    {
        $templates = $vault->account->templates()->orderBy('name', 'asc')->get();
        $templatesCollection = $templates->map(function ($template) use ($vault) {
            return [
                'id' => $template->id,
                'name' => $template->name,
                'is_default' => $vault->default_template_id === $template->id,
            ];
        });

        // users
        $usersInAccount = $vault->account->users()->whereNotNull('email_verified_at')->get();
        $usersInVault = $vault->users()->get();
        $usersInAccount = $usersInAccount->diff($usersInVault);
        $usersInAccountCollection = $usersInAccount->map(function ($user) use ($vault) {
            return self::dtoUser($user, $vault);
        });
        $usersInVaultCollection = $usersInVault->map(function ($user) use ($vault) {
            return self::dtoUser($user, $vault);
        });

        // labels
        $labels = $vault->labels()
            ->withCount('contacts')
            ->orderBy('name', 'asc')
            ->get();

        $labelsCollection = $labels->map(function ($label) use ($vault) {
            return self::dtoLabel($vault, $label);
        });

        $labelColorsCollection = collect();
        $labelColorsCollection->push([
            'bg_color' => 'bg-neutral-200',
            'text_color' => 'text-neutral-800',
        ]);
        $labelColorsCollection->push([
            'bg_color' => 'bg-red-200',
            'text_color' => 'text-red-600',
        ]);
        $labelColorsCollection->push([
            'bg_color' => 'bg-amber-200',
            'text_color' => 'text-amber-600',
        ]);
        $labelColorsCollection->push([
            'bg_color' => 'bg-emerald-200',
            'text_color' => 'text-emerald-600',
        ]);
        $labelColorsCollection->push([
            'bg_color' => 'bg-slate-200',
            'text_color' => 'text-slate-600',
        ]);
        $labelColorsCollection->push([
            'bg_color' => 'bg-sky-200',
            'text_color' => 'text-sky-600',
        ]);

        // contact important date types
        $dateTypesCollection = VaultImportantDateTypesViewHelper::data($vault);

        return [
            'templates' => $templatesCollection,
            'users_in_vault' => $usersInVaultCollection,
            'users_in_account' => $usersInAccountCollection,
            'labels' => $labelsCollection,
            'label_colors' => $labelColorsCollection,
            'contact_important_date_types' => $dateTypesCollection,
            'url' => [
                'template_update' => route('vault.settings.template.update', [
                    'vault' => $vault->id,
                ]),
                'user_store' => route('vault.settings.user.store', [
                    'vault' => $vault->id,
                ]),
                'label_store' => route('vault.settings.label.store', [
                    'vault' => $vault->id,
                ]),
                'contact_date_important_date_type_store' => route('vault.settings.important_date_type.store', [
                    'vault' => $vault->id,
                ]),
                'update' => route('vault.settings.update', [
                    'vault' => $vault->id,
                ]),
                'destroy' => route('vault.settings.destroy', [
                    'vault' => $vault->id,
                ]),
            ],
        ];
    }

    public static function dtoUser(User $user, Vault $vault): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'permission' => VaultHelper::getPermission($user, $vault),
            'url' => [
                'update' => route('vault.settings.user.update', [
                    'vault' => $vault->id,
                    'user' => $user->id,
                ]),
                'destroy' => route('vault.settings.user.destroy', [
                    'vault' => $vault->id,
                    'user' => $user->id,
                ]),
            ],
        ];
    }

    public static function dtoLabel(Vault $vault, Label $label): array
    {
        return [
            'id' => $label->id,
            'name' => $label->name,
            'count' => $label->contacts_count,
            'bg_color' => $label->bg_color,
            'text_color' => $label->text_color,
            'url' => [
                'update' => route('vault.settings.label.update', [
                    'vault' => $vault->id,
                    'label' => $label->id,
                ]),
                'destroy' => route('vault.settings.label.destroy', [
                    'vault' => $vault->id,
                    'label' => $label->id,
                ]),
            ],
        ];
    }
}
