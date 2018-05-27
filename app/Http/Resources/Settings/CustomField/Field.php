<?php

namespace App\Http\Resources\Settings\CustomField;

use Illuminate\Http\Resources\Json\Resource;
use App\Http\Resources\Settings\CustomField\DefaultCustomFieldType as DefaultCustomFieldTypeResource;

class Field extends Resource
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
            'object' => 'field',
            'name' => $this->name,
            'description' => $this->description,
            'required' => (bool) $this->required,
            'default_custom_field_type' => new DefaultCustomFieldTypeResource($this->defaultCustomFieldType),
            'field_choices' =>
            'custom_field' => [
                'id' => $this->customField->id,
            ],
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => $this->created_at->format(config('api.timestamp_format')),
            'updated_at' => (is_null($this->updated_at) ? null : $this->updated_at->format(config('api.timestamp_format'))),
        ];
    }
}
