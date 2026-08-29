<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * The user a token acts as, as the API hands it out.
 *
 * The envelope is the one every resource here will use: a type, an id, the
 * attributes, and a link back to the thing itself. The id is a string because
 * it is an identifier rather than a number to do arithmetic with, and the
 * timestamps are Unix integers because they carry no zone of their own and a
 * client can put them in whichever one its reader is sitting in.
 *
 * Credentials never appear. Not the password, not the token, not the second
 * factor, not the address the last sign in came from.
 *
 * @mixin User
 */
class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
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
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.me'),
            ],
        ];
    }
}
