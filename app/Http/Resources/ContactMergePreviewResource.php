<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ContactMergePreviewResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array<string, mixed> $preview */
        $preview = $this->resource;

        return [
            'type' => 'contact_merge_preview',
            'attributes' => [
                'kept' => $preview['kept'],
                'other' => $preview['other'],
                'conflicts' => $preview['conflicts'],
                'moves' => $preview['moves'],
                'collisions' => $preview['collisions'],
                'set_aside' => $preview['set_aside'],
                'folds' => $preview['folds'],
                'fingerprint' => $preview['fingerprint'],
            ],
        ];
    }
}
