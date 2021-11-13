<?php

namespace App\ExportResources;

use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CountResourceCollection extends AnonymousResourceCollection
{
    /**
     * Transform the resource into a JSON array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'count' => $this->count(),
            'values' => parent::toArray($request),
        ];
    }
}
