<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ContactMerge;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ContactMerge */
class ContactMergeResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'contact_merge',
            'id' => $this->uuid,
            'attributes' => [
                'kept_contact_id' => $this->keptContact->uuid,
                'merged_contact_id' => $this->mergedContact->uuid,
                'resolutions' => $this->resolutions,
                'moved' => $this->ledger,
                'stands' => $this->stands(),
                'undone_at' => $this->undone_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
            ],
        ];
    }
}
