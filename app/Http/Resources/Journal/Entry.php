<?php

namespace App\Http\Resources\Journal;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @extends JsonResource<\App\Models\Journal\Entry>
 */
class Entry extends JsonResource
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
            'object' => 'entry',
            'title' => $this->title,
            'post' => $this->post,
            'url' => route('api.entry', $this->id),
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
