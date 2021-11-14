<?php

namespace App\ExportResources;

use Illuminate\Support\Str;
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
            'type' => Str::of($this->collects)->afterLast('\\')->kebab()->replace('-', '_'),
            'values' => parent::toArray($request),
        ];
    }
}
