<?php

namespace App\Vault\ManageVaultImportantDateTypes\Web\ViewHelpers;

use App\Models\Vault;
use Illuminate\Support\Collection;
use App\Models\ContactImportantDateType;

class VaultImportantDateTypesViewHelper
{
    public static function data(Vault $vault): Collection
    {
        $types = $vault->contactImportantDateTypes;
        $typesCollection = $types->map(function ($type) use ($vault) {
            return self::dto($type, $vault);
        });

        return $typesCollection;
    }

    public static function dto(ContactImportantDateType $type, Vault $vault): array
    {
        return [
            'id' => $type->id,
            'label' => $type->label,
            'internal_type' => $type->internal_type,
            'can_be_deleted' => $type->can_be_deleted,
            'url' => [
                'update' => route('vault.settings.important_date_type.update', [
                    'vault' => $vault->id,
                    'type' => $type->id,
                ]),
                'destroy' => route('vault.settings.important_date_type.destroy', [
                    'vault' => $vault->id,
                    'type' => $type->id,
                ]),
            ],
        ];
    }
}
