<?php

namespace App\Http\Resources\Conversation;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Conversation\Message as MessageResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\Settings\ContactFieldType\ContactFieldType as ContactFieldTypeResource;

/**
 * @extends JsonResource<\App\Models\Contact\Conversation>
 */
class Conversation extends JsonResource
{
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
            'object' => 'conversation',
            'happened_at' => DateHelper::getTimestamp($this->happened_at),
            'messages' => MessageResource::collection($this->messages),
            'contact_field_type' => new ContactFieldTypeResource($this->contactFieldType),
            'url' => route('api.conversation', $this->id),
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
