<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\ContactDuplicateCandidate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin ContactDuplicateCandidate */
class ContactDuplicateCandidateResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'contact_duplicate_candidate',
            'id' => (string) $this->id,
            'attributes' => [
                'contact_id' => $this->contact->uuid,
                'other_contact_id' => $this->otherContact->uuid,
                'contact_name' => $this->contact->name,
                'other_contact_name' => $this->otherContact->name,
                'score' => $this->score,
                'reasons' => $this->reasons,
                'dismissed_at' => $this->dismissed_at?->timestamp,
                'computed_at' => $this->computed_at->timestamp,
            ],
        ];
    }
}
