<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * What a merge would do, exactly as the action reported it.
 *
 * The resource wraps rather than reshapes, because the screen and this endpoint
 * have to be told the same thing, and the fingerprint has to come back out so a
 * client can hand it in again when it confirms.
 */
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
                'fingerprint' => $preview['fingerprint'],
            ],
        ];
    }
}
