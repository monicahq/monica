<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/** @mixin User */
class UserResource extends JsonResource
{
    /** @return array<string, mixed> */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'user',
            'id' => (string) $this->id,
            'attributes' => [
                'first_name' => $this->first_name,
                'last_name' => $this->last_name,
                'email' => $this->email,
                'email_verified' => $this->email_verified_at !== null,
                'locale' => $this->locale,
                'timezone' => $this->timezone,
                'time_format' => $this->time_format->value,
                'date_format' => $this->date_format?->value,
                'name_order' => $this->name_order->value,
                'number_format' => $this->number_format?->value,
                'contact_list_grouping' => $this->contact_list_grouping,
                'contact_list_sort' => $this->contact_list_sort,
                'contact_list_direction' => $this->contact_list_direction,
                'welcomed_at' => $this->welcomed_at?->timestamp,
                'full_access_offer_dismissed_at' => $this->full_access_offer_dismissed_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.me'),
            ],
        ];
    }
}
