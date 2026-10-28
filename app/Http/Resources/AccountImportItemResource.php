<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\AccountImportReasonEnum;
use App\Models\AccountImportItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin AccountImportItem */
class AccountImportItemResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        $reason = AccountImportReasonEnum::tryFrom((string) $this->reason);

        return [
            'type' => 'account_import_item',
            'id' => (string) $this->id,
            'attributes' => [
                'kind' => $this->kind,
                'source_uuid' => $this->source_uuid,
                'reason' => $reason === null ? null : __($reason->label()),
            ],
        ];
    }
}
