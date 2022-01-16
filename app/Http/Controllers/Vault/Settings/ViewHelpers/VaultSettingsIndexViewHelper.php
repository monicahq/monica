<?php

namespace App\Http\Controllers\Vault\Settings\ViewHelpers;

use App\Models\Vault;

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

        return [
            'templates' => $templatesCollection,
            'url' => [
                'template_update' => route('vault.settings.template.update', [
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
}
