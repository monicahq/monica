<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AccountExport;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin AccountExport */
class AccountExportResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'account_export',
            'id' => $this->uuid,
            'attributes' => [
                'status' => $this->status->value,
                'vaults' => Vault::query()
                    ->where('account_id', $this->account_id)
                    ->whereIn('id', $this->vault_ids)
                    ->orderBy('position')
                    ->orderBy('id')
                    ->pluck('uuid')
                    ->all(),
                'size_in_bytes' => $this->size_in_bytes,
                'ready_at' => $this->ready_at?->timestamp,
                'expires_at' => $this->expires_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.accountExports.show', $this->resource),
            ],
        ];
    }
}
