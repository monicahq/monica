<?php

namespace App\Http\Resources\Journal;

use App\Helpers\DateHelper;
use Illuminate\Http\Resources\Json\Resource;

class Entry extends Resource
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
            'object' => 'entry',
            'title' => $this->title,
            'post' => $this->post,
            'account' => [
                'id' => $this->account_id,
            ],
            'created_at' => DateHelper::getTimestamp($this->created_at),
            'updated_at' => DateHelper::getTimestamp($this->updated_at),
        ];
    }
}
