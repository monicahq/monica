<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

class ContactCategoryChangeResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        /** @var array{changed: list<Contact>, unchanged: list<array{contact: Contact, reason: string}>} $outcome */
        $outcome = $this->resource;

        return [
            'type' => 'contact_category_change',
            'attributes' => [
                'changed' => array_map(fn (Contact $contact): string => $contact->uuid, $outcome['changed']),
                'unchanged' => array_map(fn (array $unchanged): array => [
                    'contact' => $unchanged['contact']->uuid,
                    'reason' => $unchanged['reason'],
                ], $outcome['unchanged']),
            ],
        ];
    }
}
