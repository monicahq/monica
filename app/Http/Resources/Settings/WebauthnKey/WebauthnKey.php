<?php

namespace App\Http\Resources\Settings\WebauthnKey;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class WebauthnKey extends Resource
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
            'object' => 'webauthnKey',
            'name' => $this->name,
            'counter' => $this->counter,
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
