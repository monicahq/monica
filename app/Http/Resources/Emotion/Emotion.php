<?php

namespace App\Http\Resources\Emotion;

use Illuminate\Http\Resources\Json\JsonResource;

class Emotion extends JsonResource
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
            'object' => 'emotion',
            'name' => $this->name,
        ];
    }
}
