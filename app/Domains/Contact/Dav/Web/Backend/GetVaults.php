<?php

namespace App\Domains\Contact\Dav\Web\Backend;

use App\Models\Vault;
use Illuminate\Support\Collection;

trait GetVaults
{
    /**
     * Returns the current vault.
     *
     * @return \Illuminate\Support\Collection<array-key,Vault>
     */
    public function vaults(?string $collectionId = null, int $permission = Vault::PERMISSION_VIEW): Collection
    {
        $vaults = $this->user->vaults()
            ->wherePivot('permission', '<=', $permission);

        if ($collectionId !== null) {
            $vaults = $vaults->where('id', $collectionId);
        }

        return $vaults->get();
    }
}
