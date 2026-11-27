<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use App\Models\Vault;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin Vault */
class VaultResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'vault',
            'id' => $this->uuid,
            'attributes' => [
                'slug' => $this->slug,
                'name' => $this->name,
                'description' => $this->description,
                'colour' => $this->colour,
                'position' => $this->position,
                'is_default' => $this->is_default,
                'contact_count' => $this->contact_count,
                'last_visited_at' => $this->last_visited_at?->timestamp,
                'faces' => $this->faces
                    ->map(fn (Contact $contact): array => [
                        'id' => $contact->uuid,
                        'name' => $contact->name,
                        'avatar_url' => $contact->avatar === null
                            ? null
                            : route('api.v1.contacts.avatar.show', ['vault' => $this->resource, 'contact' => $contact->uuid]),
                    ])
                    ->values()
                    ->all(),
                'upcoming_reminder_count' => (int) $this->upcoming_reminder_count,
                'shows_people' => $this->shows_people,
                'shows_things' => $this->shows_things,
                'shows_knowledge' => $this->shows_knowledge,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.vaults.show', $this->resource),
                'web' => route('vaults.show', $this->resource),
            ],
        ];
    }
}
