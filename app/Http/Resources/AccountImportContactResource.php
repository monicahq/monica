<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\AccountImportItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin AccountImportItem */
class AccountImportContactResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'account_import_contact',
            'id' => (string) $this->source_uuid,
            'attributes' => [
                'name' => $this->name,
            ],
        ];
    }
}
