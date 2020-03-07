<?php

namespace App\Http\Resources\Settings\Currency;

use Illuminate\Http\Resources\Json\JsonResource;

class Currency extends JsonResource
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
            'object' => 'currency',
            'iso' => $this->iso,
            'name' => $this->name,
            'symbol' => $this->symbol,
        ];
    }
}
