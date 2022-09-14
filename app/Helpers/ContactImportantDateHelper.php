<?php

namespace App\Helpers;

use App\Models\ContactImportantDateType;
use App\Models\Vault;
use Illuminate\Support\Facades\Cache;

class ContactImportantDateHelper
{
    /**
     * Get the important date type from the vault.
     *
     * @return null|ContactImportantDateType
     */
    public static function getImportantDateType(string $type, int $vault_id): ?ContactImportantDateType
    {
        return Cache::store('array')->remember("ImportantDateType:{$vault_id}:{$type}", 5,
            fn () => ContactImportantDateType::where([
                'vault_id' => $vault_id,
                'internal_type' => $type,
            ])
            ->first()
        );
    }
}
