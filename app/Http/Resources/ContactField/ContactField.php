<?php

namespace App\Http\Resources\ContactField;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Http\Resources\Contact\ContactShort as ContactShortResource;
use App\Http\Resources\Settings\ContactFieldType\ContactFieldType as ContactFieldTypeResource;

/**
 * @extends JsonResource<\App\Models\Contact\ContactField>
 */
class ContactField extends JsonResource
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
            'object' => 'contactfield',
            'content' => $this->data,
            'contact_field_type' => new ContactFieldTypeResource($this->contactFieldType),
            'labels' => ContactFieldLabel::collection($this->labels),
            'account' => [
                'id' => $this->account_id,
            ],
            'contact' => new ContactShortResource($this->contact),
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
