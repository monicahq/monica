<?php

namespace App\Http\Resources\Conversation;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Conversation\Message as MessageResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\Settings\ContactFieldType\ContactFieldType as ContactFieldTypeResource;

class Conversation extends Resource
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
            'object' => 'conversation',
            'happened_at' => DateHelper::getTimestamp($this->happened_at),
            'messages' => MessageResource::collection($this->messages),
            'contact_field_type' => new ContactFieldTypeResource($this->contactFieldType),
            'account' => [
                'id' => $this->account->id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
