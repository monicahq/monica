<?php

namespace App\Http\Resources\Settings\ContactFieldType;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class ContactFieldType extends Resource
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
            'object' => 'contactfieldtype',
            'name' => $this->name,
            'fontawesome_icon' => $this->fontawesome_icon,
            'protocol' => $this->protocol,
            'delible' => (bool) $this->delible,
            'type' => $this->type,
            'account' => [
                'id' => $this->account->id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
