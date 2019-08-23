<?php

namespace App\Http\Resources\Settings\U2fKey;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class U2fKey extends Resource
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
            'object' => 'u2fkey',
            'name' => $this->name,
            'counter' => $this->counter,
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
