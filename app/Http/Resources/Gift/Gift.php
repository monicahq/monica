<?php

namespace App\Http\Resources\Gift;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Gift extends Resource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'object' => 'gift',
            'about_object_type' => $this->about_object_type,
            'about_object_id' => $this->about_object_id,
            'name' => $this->name,
            'comment' => $this->comment,
            'url' => $this->url,
            'value_in_dollars' => $this->value_in_dollars,
            'is_an_idea' => $this->is_an_idea,
            'has_been_offered' => $this->has_been_offered,
            'date_offered' => $this->date_offered,
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
