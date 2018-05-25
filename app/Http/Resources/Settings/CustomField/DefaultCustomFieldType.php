<?php

namespace App\Http\Resources\Settings\CustomField;

use Illuminate\Http\Resources\Json\Resource;

class DefaultCustomFieldType extends Resource
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
            'object' => 'defaultcustomfieldtype',
            'type' => $this->type,
        ];
    }
}
