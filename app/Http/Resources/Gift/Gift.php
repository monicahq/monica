<?php

namespace App\Http\Resources\Gift;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;

class Gift extends Resource
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
            'object' => 'gift',
            'is_for' => new ContactShortResource($this->recipient),
            'name' => $this->name,
            'comment' => $this->comment,
            'url' => $this->url,
            'value' => $this->value,
            'is_an_idea' => $this->is_an_idea,
            'has_been_offered' => $this->has_been_offered,
            'date_offered' => $this->date_offered,
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
