<?php

namespace App\Http\Resources\Contact;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @extends JsonResource<\App\Models\Contact\Contact>
 */
class ContactSearch extends JsonResource
{
    use ContactBase;

    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'object' => 'contact',
            'route' => route('people.show', $this),
            'complete_name' => $this->name,
            'description' => $this->description,
            'initials' => $this->getInitials(),
            'is_me' => $this->isMe(),
            'is_starred' => $this->is_starred,
            'information' => [
                'avatar' => [
                    'url' => $this->getAvatarUrl(),
                    'source' => $this->avatar_source,
                    'default_avatar_color' => $this->default_avatar_color,
                ],
            ],
            'url' => $this->when(! $this->is_partial, route('api.contact', $this->id)),
            'account' => [
                'id' => $this->account_id,
            ],
        ];
    }
}
