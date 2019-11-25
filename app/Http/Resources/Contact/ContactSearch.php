<?php

namespace App\Http\Resources\Contact;

class ContactSearch extends Contact
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'contact',
            'route' => route('people.show', $this),
            'complete_name' => $this->name,
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
            'account' => [
                'id' => $this->account->id,
            ],
        ];
    }
}
