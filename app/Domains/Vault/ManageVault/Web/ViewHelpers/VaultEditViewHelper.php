<?php

namespace App\Domains\Vault\ManageVault\Web\ViewHelpers;

use App\Models\Vault;

class VaultEditViewHelper
{
    public static function data(Vault $vault): array
    {
        return [
            'id' => $vault->id,
            'name' => $vault->name,
            'description' => $vault->description,
            'url' => [
                'update' => route('vault.update', [
                    'vault' => $vault,
                ]),
                'back' => route('vault.index'),
            ],
        ];
    }
}
