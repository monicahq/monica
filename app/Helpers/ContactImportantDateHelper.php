<?php

namespace App\Helpers;

use App\Models\ContactImportantDateType;
use App\Models\Vault;
use Illuminate\Support\Facades\Cache;

class ContactImportantDateHelper
{
    /**
     * Get the important date type from the vault.
     */
    public static function getImportantDateType(string $type, string $vaultId): ?ContactImportantDateType
    {
        return Cache::store('array')->remember("ImportantDateType:{$vaultId}:{$type}", 5,
            fn () => ContactImportantDateType::where([
                'vault_id' => $vaultId,
                'internal_type' => $type,
            ])
            ->first()
        );
    }
}
