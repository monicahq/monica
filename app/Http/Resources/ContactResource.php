<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Contact;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Override;

/**
 * A contact, as the API hands it out.
 *
 * The address is the uuid and never the primary key, for the same reason a
 * vault's is: a sequential number would say how many people an account knows
 * and let one address be guessed from the one beside it.
 *
 * The assembled name is carried alongside every part it was assembled from.
 * Both are needed and neither can be derived by a caller: the parts alone do
 * not say which order this person's name is written in, and the assembled name
 * alone cannot be edited.
 *
 * Two things are deliberately absent. What is written on the contact, because
 * that comes from the structure it renders from and is a surface of its own;
 * the version is named here so that surface has its identifier. And when the
 * contact was last looked at, because that is a person clicking in a browser
 * and says nothing to a program.
 *
 * @mixin Contact
 */
class ContactResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    #[Override]
    public function toArray(Request $request): array
    {
        return [
            'type' => 'contact',
            'id' => $this->uuid,
            'attributes' => [
                'name' => $this->name,
                'prefix' => $this->prefix,
                'first_name' => $this->first_name,
                'middle_name' => $this->middle_name,
                'last_name' => $this->last_name,
                'suffix' => $this->suffix,
                'maiden_name' => $this->maiden_name,
                'nickname' => $this->nickname,
                'name_order' => $this->name_order?->value,
                'initials' => $this->initials,
                'avatar_colour' => $this->avatar_colour,
                'avatar' => $this->avatarLocation(),
                'is_favourite' => $this->is_favourite,
                'is_archived' => $this->is_archived,
                'is_partial' => $this->is_partial,
                'is_me' => $this->is_me,
                'template_version_id' => $this->template_version_id,
                'template_name' => $this->templateVersion->template->name,
                'last_activity_at' => $this->last_activity_at?->timestamp,
                'created_at' => $this->created_at->timestamp,
                'updated_at' => $this->updated_at?->timestamp,
            ],
            'links' => [
                'self' => route('api.v1.contacts.show', [$this->vault->uuid, $this->uuid]),
            ],
        ];
    }

    /**
     * Where the picture can be read, or nothing when the contact is drawn as
     * its initials.
     *
     * The address is the one the application serves files from, which is behind
     * the session rather than the token: a document is as private as the record
     * it sits on, and no endpoint streams a vault's files yet. The identifier is
     * carried beside it so that a caller can name the file the day one does.
     *
     * @return array{id: string, url: string}|null
     */
    private function avatarLocation(): ?array
    {
        if ($this->resource->avatar === null) {
            return null;
        }

        return [
            'id' => $this->resource->avatar->uuid,
            'url' => route('files.show', ['vault' => $this->vault, 'file' => $this->resource->avatar]),
        ];
    }
}
