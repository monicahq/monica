<?php

namespace App\Http\Resources\ContactField;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\Settings\ContactFieldType\ContactFieldType as ContactFieldTypeResource;

class ContactField extends Resource
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
            'object' => 'contactfield',
            'content' => $this->data,
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
