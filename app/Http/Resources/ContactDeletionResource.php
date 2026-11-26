<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ContactDeletionResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array{deleted: list<Contact>, kept: list<array{contact: Contact, reason: string}>, missing: list<string>} $outcome */
        $outcome = $this->resource;

        return [
            'type' => 'contact_deletion',
            'attributes' => [
                'deleted' => array_map(fn (Contact $contact): string => $contact->uuid, $outcome['deleted']),
                'kept' => array_map(fn (array $kept): array => [
                    'contact' => $kept['contact']->uuid,
                    'reason' => $kept['reason'],
                ], $outcome['kept']),
                'missing' => $outcome['missing'],
            ],
        ];
    }
}
